<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\{LightspotSubmission,PositiveMoment};
use App\Services\{LaptopImages,LightspotProcessor,MomentModerator,MomentIllustrator};
class LaptopTick extends Command {
 protected $signature='laptop:tick {--limit=5}';protected $description='Hervat opgeslagen tekstcontroles en verwerk teruggekomen laptopbeelden';
 public function handle():int {
  $lock=Cache::lock('laptop-pipeline-tick',300);if(!$lock->get())return self::SUCCESS;
  try{
   $limit=max(1,min(20,(int)$this->option('limit')));
   $eligible=fn($q)=>$q->where('is_suspended',false)->whereNotNull('email_verified_at')->whereNotNull('community_accepted_at');
   foreach(LightspotSubmission::whereIn('status',['pending','image_wait'])->whereHas('user',$eligible)->where(fn($q)=>$q->whereNull('ai_retry_at')->orWhere('ai_retry_at','<=',now()))->orderByRaw("CASE WHEN ai_retry_at IS NULL THEN 1 ELSE 0 END")->oldest('updated_at')->limit($limit)->get() as $s)app(LightspotProcessor::class)->process($s);
   foreach(PositiveMoment::where('status','pending')->whereHas('user',$eligible)->where(fn($q)=>$q->whereNull('ai_retry_at')->orWhere('ai_retry_at','<=',now()))->oldest('updated_at')->limit($limit)->get() as $m)app(MomentModerator::class)->review($m,$m->revision);
   foreach(PositiveMoment::where('status','approved')->whereHas('user',$eligible)->whereNotNull('image_accepted_at')->whereIn('image_state',['queued','waiting'])->where(fn($q)=>$q->whereNull('ai_retry_at')->orWhere('ai_retry_at','<=',now()))->oldest('updated_at')->limit($limit)->get() as $m){
    try{app(MomentIllustrator::class)->generate($m->id,$m->revision);}catch(\App\Services\AiWaitException $e){app(\App\Services\AiAvailability::class)->defer($m,$e);}catch(\Throwable $e){$m->update(['image_state'=>'failed']);}
   }
   // Imported news still uses its existing publishing commands; this tick only
   // finishes an illustration already requested and returned by the laptop.
   if(app(LaptopImages::class)->enabled())foreach(\App\Models\LaptopImageJob::where('target_type','article')->whereIn('status',['generated','completed'])->oldest('updated_at')->limit($limit)->get() as $job){
    $a=\App\Models\Article::find($job->target_id);if(!app(LaptopImages::class)->active($job)){$job->update(['status'=>'cancelled']);continue;}if(!$a||$a->origin==='community'||app(\App\Services\ArticlePhotoFinder::class)->current($a))continue;
    try{app(\App\Services\ArticlePhotoFinder::class)->attach($a);}catch(\Throwable $e){/* Existing news pipeline reports/retries editorial checks. */}
   }
   $this->info('Wachtende controles en teruggekomen beelden verwerkt.');return self::SUCCESS;
  }finally{$lock->release();}
 }
}
