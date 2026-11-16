<?php
namespace App\Services;
use App\Models\{User,Article,PositiveMoment,LaptopImageJob,LocalAiJob,SupportPledge};
use Illuminate\Support\Facades\{Cache,DB,File,Storage,Schema};
class AccountErasure {
 public function erase(User $user,bool $keepSupport=false):void {
  // Same media lock as generation/uploads and cleanup. Fail without deleting on lock/provider failure.
  $ownerLock=Cache::lock('support-owner-'.$user->id,2100);
  if(!$ownerLock->get())throw new \RuntimeException('Een betaling wordt verwerkt. Probeer later opnieuw.');
  $lock=Cache::lock('site-media-maintenance',2100);
  if(!$lock->get()){$ownerLock->release();throw new \RuntimeException('Er worden beelden verwerkt. Probeer je account later opnieuw te verwijderen.');}
  try{
   $pledges=SupportPledge::where('user_id',$user->id)->where('frequency','monthly')->whereNull('stopped_at')->get();
   if($keepSupport){
    if(!$user->hasVerifiedEmail())throw new \RuntimeException('Bevestig je e-mailadres voordat je zonder account blijft steunen.');
    foreach($pledges as $pledge)app(DetachedSupport::class)->sendLink($pledge,$user->email);
   }else foreach($pledges as $pledge)app(SupportPayments::class)->stop($pledge);
   $paths=[];$newsPaths=[];
   DB::transaction(function()use($user,$keepSupport,&$paths,&$newsPaths){
    $u=User::whereKey($user->id)->lockForUpdate()->firstOrFail();
    $articles=Article::where('author_id',$u->id)->get();$articleIds=$articles->pluck('id');
    $moments=PositiveMoment::withTrashed()->where('user_id',$u->id)->get();$momentIds=$moments->pluck('id');
    $commentIds=$u->comments()->pluck('id');$storyIds=$u->submissions()->pluck('id');
    // Queued text tasks are encrypted and deduplicated without owner IDs. Invalidate this transient
    // queue in full, so neither copied context nor a late laptop answer can restore deleted text.
    // Other contributions remain intact and their scheduled retries create fresh tasks.
    LocalAiJob::query()->delete();
    foreach(LaptopImageJob::where(fn($q)=>$q->where(fn($q)=>$q->where('target_type','moment')->whereIn('target_id',$momentIds))->orWhere(fn($q)=>$q->where('target_type','article')->whereIn('target_id',$articleIds)))->get() as $job){
     if($path=$job->result['path']??null)$paths[]=$path;
     if($path=$job->result['url']??null)$newsPaths[]=$path;
     $job->delete();
    }
    foreach($u->photos()->get() as $photo)if($photo->path)$paths[]=$photo->path;
    foreach($moments as $m){if($m->image_path)$paths[]=$m->image_path;DB::table('content_reports')->where('kind','moment')->where('target_id',$m->id)->delete();}
    foreach(['comment'=>$commentIds,'story'=>$storyIds,'article'=>$articleIds] as $kind=>$ids){DB::table('content_reports')->where('kind',$kind)->whereIn('target_id',$ids)->delete();}
    foreach($articles as $a){if($a->photo['url']??null)$newsPaths[]=$a->photo['url'];
     if(Schema::hasTable('social_publications'))\App\Models\SocialPublication::where('article_id',$a->id)->delete();
     // Delete source text cached in illustration metadata as well as the public article.
     foreach(File::glob(storage_path('app/illustration-backups/'.$a->id.'-*.json')) as $path)File::delete($path);
     $a->delete();
    }
    // Database cascades remove moments (including trash), comments, submissions, photos,
    // activity, social logins, consents and privacy requests. Explicitly remove sessions/reset tokens.
    foreach(DB::table('user_article_likes')->where('user_id',$u->id)->get() as $like)Article::whereKey($like->article_id)->where('likes_count','>',0)->decrement('likes_count');
    DB::table('content_reports')->where('user_id',$u->id)->delete();
    DB::table('password_reset_tokens')->where('email',$u->email)->delete();
    if(Schema::hasTable('sessions'))DB::table('sessions')->where('user_id',$u->id)->delete();
    SupportPledge::where('user_id',$u->id)->get()->each(function($p)use($keepSupport,$u){
     $continuing=$keepSupport&&$p->frequency==='monthly'&&!$p->stopped_at;
     $p->update(['management_email'=>$continuing?$u->email:null,'management_email_hash'=>$continuing?DetachedSupport::emailHash($u->email):null,'detached_at'=>now(),'user_id'=>null,'checkout_url'=>null]);
    });
    foreach(array_unique($paths) as $path)DB::table('privacy_file_deletions')->insertOrIgnore(['path'=>$path,'created_at'=>now()]);
    app(DeletionLedger::class)->record($u,$keepSupport);
    $u->delete();
   },3);
   foreach(array_unique($paths) as $path){if(preg_match('~^(moment-images/[a-f0-9]{48}\.(jpg|webp)|profile-photos/[a-f0-9-]{36}\.jpg)$~D',$path)){try{if(Storage::disk('local')->delete($path))DB::table('privacy_file_deletions')->where('path',$path)->delete();}catch(\Throwable $e){/* The scheduled erasure task retries this private path. */}}}
   $refs=app(\App\Services\Maintenance\FileReferences::class)->all();
   foreach(array_unique($newsPaths) as $path)if(preg_match('~^/news-images/[a-f0-9]{64}\.(jpg|png|webp)$~D',$path)&&!isset($refs[$path]))File::delete(public_path(ltrim($path,'/')));
  }finally{$lock->release();$ownerLock->release();}
 }
}
