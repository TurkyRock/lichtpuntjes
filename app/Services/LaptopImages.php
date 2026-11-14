<?php
namespace App\Services;
use App\Models\{Article,LaptopImageJob,LightspotSubmission,PositiveMoment};
use Illuminate\Support\Facades\{DB,File};
class LaptopImages {
 public function enabled():bool{return (bool)config('laptop-images.enabled')&&config('news_images.provider')==='laptop';}
 public function hashArticle(Article $a):string{return hash('sha256',app(ArticleVisualBrief::class)->contentHash($a).'|'.app(LichtpuntIllustrationPrompt::class)->style($a));}
 public function hashMoment(PositiveMoment $m):string{return hash('sha256',$m->id.'|'.$m->revision.'|'.$m->body);}
 public function active(LaptopImageJob $job):bool {
  if($job->target_type==='article'){
   $a=Article::find($job->target_id);if(!$a||$a->hidden_at||in_array($a->status,['rejected','insufficient','doubt'])||!hash_equals($job->content_hash,$this->hashArticle($a)))return false;
   if($a->origin==='community')return LightspotSubmission::where('article_id',$a->id)->where('status','image_wait')->whereHas('user',fn($q)=>$q->where('is_suspended',false)->whereNotNull('email_verified_at')->whereNotNull('community_accepted_at'))->exists();
   return true;
  }
  $m=PositiveMoment::with('user')->find($job->target_id);
  return $m&&$m->status==='approved'&&$m->image_accepted_at&&$m->user&&!$m->user->is_suspended&&$m->user->hasVerifiedEmail()&&$m->user->community_accepted_at&&!$m->hasMomentImage()&&hash_equals($job->content_hash,$this->hashMoment($m));
 }
 public function enqueue(string $kind,int $id,string $hash,array $payload,int $attempt=1):LaptopImageJob {
  return LaptopImageJob::firstOrCreate(['target_type'=>$kind,'target_id'=>$id,'content_hash'=>$hash,'attempt'=>$attempt],['payload'=>$payload,'status'=>'queued','available_at'=>now()]);
 }
 public function claim(string $worker):?array {
  DB::table('laptop_image_workers')->updateOrInsert(['worker_id'=>$worker],['last_seen_at'=>now()]);
  return DB::transaction(function()use($worker){
   // One short global database row lock serializes claims, including expired leases.
   DB::table('laptop_image_workers')->where('worker_id',$worker)->lockForUpdate()->first();
   for($i=0;$i<50;$i++){
    $job=LaptopImageJob::where(fn($q)=>$q->where('status','queued')->orWhere(fn($q)=>$q->where('status','processing')->where('lease_until','<=',now())))->where(fn($q)=>$q->whereNull('available_at')->orWhere('available_at','<=',now()))->oldest('id')->lockForUpdate()->first();
    if(!$job)return null;
    if(!$this->active($job)){$job->update(['status'=>'cancelled','lease_hash'=>null]);continue;}
    if($job->claims>=(int)config('laptop-images.max_claims',5)){if($job->target_type==='moment')PositiveMoment::whereKey($job->target_id)->update(['image_state'=>'failed']);$job->update(['status'=>'failed','failure_code'=>'retries_exhausted']);continue;}
    $token=bin2hex(random_bytes(32));$job->update(['status'=>'processing','worker_id'=>$worker,'claims'=>$job->claims+1,'lease_hash'=>hash('sha256',$token),'lease_until'=>now()->addSeconds(config('laptop-images.lease_seconds')),'failure_code'=>null]);
    return ['id'=>$job->id,'lease'=>$token,'lease_seconds'=>(int)config('laptop-images.lease_seconds'),'attempt'=>$job->attempt,'content_hash'=>$job->content_hash,'task'=>$job->payload];
   }
   return null;
  },3);
 }
 private function authorizeLease(LaptopImageJob $job,string $lease):void {
  abort_unless($job->status==='processing'&&is_string($job->lease_hash)&&hash_equals($job->lease_hash,hash('sha256',$lease))&&$job->lease_until?->isFuture(),409,'Opdracht is verlopen of overgenomen.');
 }
 public function renew(int $id,string $lease):array {
  return DB::transaction(function()use($id,$lease){$j=LaptopImageJob::whereKey($id)->lockForUpdate()->firstOrFail();$this->authorizeLease($j,$lease);if(!$this->active($j)){$j->update(['status'=>'cancelled','lease_hash'=>null]);return ['active'=>false];}$j->update(['lease_until'=>now()->addSeconds(config('laptop-images.lease_seconds'))]);DB::table('laptop_image_workers')->where('worker_id',$j->worker_id)->update(['last_seen_at'=>now()]);return ['active'=>true];});
 }
 public function fail(int $id,string $lease,string $code):void {
  DB::transaction(function()use($id,$lease,$code){$j=LaptopImageJob::whereKey($id)->lockForUpdate()->firstOrFail();$this->authorizeLease($j,$lease);$failed=$j->claims>=config('laptop-images.max_claims');if($failed&&$j->target_type==='moment'&&$this->active($j))PositiveMoment::whereKey($j->target_id)->update(['image_state'=>'failed']);$j->update(['status'=>$failed?'failed':'queued','failure_code'=>$code,'lease_hash'=>null,'lease_until'=>null,'available_at'=>now()->addMinutes(2)]);});
 }
 public function complete(int $id,string $lease,string $bytes):array {
  $info=@getimagesizefromstring($bytes);
  abort_unless(strlen($bytes)<=(int)config('laptop-images.max_upload_bytes')&&in_array($info['mime']??'',['image/png','image/jpeg'],true)&&min($info[0]??0,$info[1]??0)>=128&&max($info[0]??0,$info[1]??0)<=2048,422,'Geen bruikbare PNG/JPEG.');
  return DB::transaction(function()use($id,$lease,$bytes,$info){
   $j=LaptopImageJob::whereKey($id)->lockForUpdate()->firstOrFail();
   // A lost HTTP reply can safely be replayed without saving a second image.
   if(in_array($j->status,['generated','completed','applied'])&&$j->completion_hash&&hash_equals($j->completion_hash,hash('sha256',$lease)))return ['saved'=>true];
   $this->authorizeLease($j,$lease);
   abort_unless($this->active($j),409,'De tekst of toestemming is inmiddels gewijzigd.');
   if($j->target_type==='moment'){
    $m=PositiveMoment::with('user')->whereKey($j->target_id)->lockForUpdate()->firstOrFail();
    // Recheck after locking the owner/target, before storing a private image.
    DB::table('users')->where('id',$m->user_id)->lockForUpdate()->first();abort_unless($this->active($j),409);
    $path=app(MomentImageFiles::class)->save($bytes);
    try{$m->update(['image_path'=>$path,'image_state'=>'ready','image_revision'=>$m->revision,'ai_retry_at'=>null,'ai_wait_reason'=>null]);$j->update(['status'=>'completed','result'=>['path'=>$path],'completion_hash'=>hash('sha256',$lease),'lease_hash'=>null,'lease_until'=>null]);}catch(\Throwable $e){MomentImageFiles::remove($path);throw $e;}
   }else{
    abort_unless($info[0]>=512&&$info[1]>=288,422);
    $a=Article::whereKey($j->target_id)->lockForUpdate()->firstOrFail();abort_unless($this->active($j),409);
    // Re-encode to remove workflow metadata and keep private prompts out of public files.
    $src=@imagecreatefromstring($bytes);abort_unless($src,422);ob_start();try{imagejpeg($src,null,92);$clean=ob_get_contents();}finally{ob_end_clean();imagedestroy($src);}
    $hash=hash('sha256',$clean);abort_if(Article::where('id','!=',$a->id)->where('photo->sha256',$hash)->exists(),422,'Dit beeld wordt al gebruikt.');$name=$hash.'.jpg';File::ensureDirectoryExists(public_path('news-images'));$path=public_path('news-images/'.$name);
    $tmp=$path.'.'.bin2hex(random_bytes(8)).'.tmp';try{if(!File::exists($path)&&(File::put($tmp,$clean)===false||!rename($tmp,$path)))throw new NewsServiceException('Afbeelding opslaan mislukt.');}finally{if(is_file($tmp))File::delete($tmp);}
    $p=$j->payload;$photo=['url'=>'/news-images/'.$name,'sha256'=>$hash,'generated'=>true,'illustrative'=>true,'creator'=>'AI / ComfyUI laptop','provider'=>'laptop','model'=>$p['model'],'style'=>$p['style'],'seed'=>$p['seed'],'subject_hash'=>$p['subject_hash'],'description'=>'AI-beeld bij: '.mb_substr($a->nl['title']??$a->source_title,0,200),'created_at'=>now()->toIso8601String()];
    $j->update(['status'=>'generated','result'=>$photo,'completion_hash'=>hash('sha256',$lease),'lease_hash'=>null,'lease_until'=>null]);
   }
   return ['saved'=>true];
  },3);
 }
 public function position(LaptopImageJob $j):?int {
  if(!in_array($j->status,['queued','processing']))return null;
  return LaptopImageJob::whereIn('status',['queued','processing'])->where('id','<=',$j->id)->count();
 }
}
