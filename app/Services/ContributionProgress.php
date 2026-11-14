<?php
namespace App\Services;
use App\Models\{LaptopImageJob,LightspotSubmission,PositiveMoment};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
class ContributionProgress {
 public function for($record,bool $en=false):array {
  $story=$record instanceof LightspotSubmission;$kind=$story?'story':'moment';$state=$record->status;
  $item=$story?($en?'story':'verhaal'):($en?'moment':'momentje');
  $base=['stage'=>1,'code'=>'review','heading'=>$en?'Your '.$item.' is safely saved':'Je '.$item.' is veilig bewaard','message'=>$en?'We review the text first. You can leave this page; processing continues.':'Eerst controleren we je tekst. Je hoeft deze pagina niet open te houden; de verwerking gaat verder.','position'=>null,'retry_at'=>null,'link'=>null,'image_url'=>null,'image_style'=>null];
  if($state==='rejected')return array_replace($base,['code'=>'revise','heading'=>$en?'A small change is needed':'Nog even aanpassen','message'=>$en?'Read the feedback and resubmit your text.':'Lees de feedback en stuur je aangepaste tekst opnieuw in.']);
  if($state==='doubt')return array_replace($base,['code'=>'human','heading'=>$en?'A human review':'Een menselijke blik','message'=>$en?'An administrator will review this. Your text remains saved.':'Een beheerder kijkt mee. Je tekst blijft bewaard.']);
  $ready=$story?$state==='published':($state==='approved'&&(!$record->image_accepted_at||$record->hasMomentImage()));
  if($ready)return array_replace($base,['stage'=>4,'code'=>'ready','heading'=>$en?'Your '.$item.' is ready!':'Jouw '.$item.' straalt!','message'=>!$story&&$record->visibility==='private'?($record->hasMomentImage()?($en?'Safely kept just for you, including your image.':'Veilig bewaard voor jou, ook het beeld blijft privé.'):($en?'Safely kept, just for you.':'Veilig bewaard, alleen voor jou.')):($en?'Ready to pass on a smile.':'Klaar om een glimlach door te geven.'),'image_url'=>$story?($record->article?->photo['url']??null):$record->momentImageUrl(),'image_style'=>$story?'photo':$record->image_style,'link'=>$story&&$record->article_id?'/?story='.$record->article_id.'&lang='.($en?'en':'nl'):null]);
  if($record->ai_retry_at&&CarbonImmutable::parse($record->ai_retry_at)->isFuture()){
   $waitingClass=$story?LightspotSubmission::class:PositiveMoment::class;$textPosition=$state==='pending'?$waitingClass::where('status','pending')->where('id','<=',$record->id)->count():null;
   $daily=$record->ai_wait_reason==='daily_quota';$at=CarbonImmutable::parse($record->ai_retry_at)->timezone('Europe/Amsterdam');
   $when=$at->isTomorrow()?($en?'tomorrow':'morgen'):$at->format('d-m');$when.=' '.$at->format('H:i');
   return array_replace($base,['stage'=>$state==='pending'?1:2,'code'=>$daily?'daily_quota':'ai_pause','heading'=>$daily?($en?'Our AI is taking a little break':'Onze AI doet even rustig aan'):($en?'A little patience for the AI check':'Even geduld voor de AI-controle'),'message'=>($daily?($en?'Today’s available AI allowance has been reached. Your text is saved. Next attempt: ':'Het beschikbare AI-dagtegoed is bereikt. Je tekst staat veilig opgeslagen. Volgende poging: '):($en?'The AI service is temporarily busy or unavailable. We will retry automatically: ':'De AI-dienst is tijdelijk druk of niet beschikbaar. We proberen automatisch opnieuw: ')).$when.'.','position'=>$textPosition,'retry_at'=>$at->toIso8601String()]);
  }
  if($state==='pending'){
   $class=$story?LightspotSubmission::class:PositiveMoment::class;$position=$class::where('status','pending')->where('id','<=',$record->id)->count();
   return array_replace($base,['position'=>$position,'message'=>$en?'Your text is waiting for review. No need to submit it again.':'Je tekst wacht op controle. Je hoeft hem niet opnieuw in te sturen.']);
  }
  if(!$story&&$record->image_state==='failed')return array_replace($base,['stage'=>2,'code'=>'failed','heading'=>$en?'Your image needs a little help':'Je beeld heeft even hulp nodig','message'=>$en?'Your text is saved. You can retry the image below.':'Je tekst is bewaard. Je kunt het beeld hieronder opnieuw aanvragen.']);
  $target=$story?($record->article?$record->article_id:null):$record->id;
  $job=$target?LaptopImageJob::where('target_type',$story?'article':'moment')->where('target_id',$target)->where('content_hash',$story?app(LaptopImages::class)->hashArticle($record->article):app(LaptopImages::class)->hashMoment($record))->latest('attempt')->first():null;
  if($job&&in_array($job->status,['generated','completed']))return array_replace($base,['stage'=>3,'code'=>'finishing','heading'=>$en?'The finishing touch':'Nog een laatste controle','message'=>$en?'Your image has arrived. We are checking and preparing it.':'Je afbeelding is binnen. We controleren en verwerken het resultaat.']);
  if($job&&$job->status==='processing'&&$job->lease_until?->isFuture())return array_replace($base,['stage'=>3,'code'=>'rendering','heading'=>$en?'Your '.$item.' is getting colour!':'Jouw '.$item.' krijgt kleur!','message'=>$en?'Your image is being created. It will appear automatically.':'Je afbeelding wordt nu gemaakt. Zodra hij klaar is, verschijnt hij bij je '.$item.'.']);
  if($job&&in_array($job->status,['failed','rejected']))return array_replace($base,['stage'=>2,'code'=>'failed','heading'=>$en?'A little extra attention':'Even extra aandacht nodig','message'=>$en?'Your text is approved and saved. The image needs a technical or editorial check.':'Je tekst is goedgekeurd en bewaard. Het beeld wacht op een technische of inhoudelijke controle.']);
  $position=$job?app(LaptopImages::class)->position($job):null;
  $offline=app(LaptopImages::class)->enabled()&&!DB::table('laptop_image_workers')->where('last_seen_at','>',now()->subMinutes(2))->exists();
  return array_replace($base,['stage'=>2,'code'=>$offline?'image_pause':'image_queue','heading'=>$en?'Approved! Your image is on its way':'Goedgekeurd! Je beeld komt eraan','position'=>$position,'message'=>$offline?($en?'The image maker is not available right now. Your place is saved and processing resumes automatically when it returns.':'De beeldenmaker is nu even niet beschikbaar. Je plek blijft bewaard; zodra hij weer draait, gaat de wachtrij vanzelf verder.'):($en?'A little patience before your '.$item.' can shine. We create images one at a time.':'Nog even geduld voordat jouw '.$item.' kan stralen. We maken de afbeeldingen één voor één.')]);
 }
}
