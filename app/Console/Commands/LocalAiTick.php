<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\{LocalAiJob,Comment,PositiveMoment};
class LocalAiTick extends Command {
 protected $signature='local-ai:tick';protected $description='Hervat lokale tekstcontroles en verwijder verlopen lokale AI-opdrachten';
 public function handle():int {
  LocalAiJob::where('expires_at','<=',now())->delete();
  if(!config('local-ai.enabled'))return self::SUCCESS;
  $lock=Cache::lock('local-ai-consume',55);if(!$lock->get())return self::SUCCESS;
  try{
   $eligible=fn($q)=>$q->where('is_suspended',false)->whereNotNull('community_accepted_at')->whereNotNull('email_verified_at');
   foreach(Comment::whereNull('moderated_by')->where('status','pending')->whereHas('user',$eligible)->oldest('updated_at')->limit(10)->get() as $c)app(\App\Services\CommentModerator::class)->review($c);
   // Recovery also works without a continuously running Laravel queue worker.
   foreach(['moment'=>PositiveMoment::class,'comment'=>Comment::class] as $kind=>$class){
    foreach($class::where('status','approved')->whereHas('user',$eligible)->where(fn($q)=>$q->whereNull('translation_state')->orWhere('translation_state','!=','ready'))->oldest('updated_at')->limit(5)->get() as $m){
     try{app(\App\Services\ContributionTranslator::class)->translate($kind,$m->id,hash('sha256',$m->body));}catch(\App\Services\LocalAiPending $e){}catch(\Throwable $e){$m->update(['translation_state'=>'failed']);}
    }
   }
  }finally{$lock->release();}
  $this->info('Lokale tekstwachtrij gecontroleerd.');return self::SUCCESS;
 }
}
