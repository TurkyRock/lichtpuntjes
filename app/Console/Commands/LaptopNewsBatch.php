<?php
namespace App\Console\Commands;
use App\Models\Article;
use App\Services\{AiAvailability,AiWaitException,ArticleImageRejected,ArticlePhotoFinder,GeminiTemporaryException,ImagePendingException,LaptopImages,LichtpuntIllustrationPrompt,NewsCategoryBalance,NewsDuplicates,NewsImporter};
use App\Support\StorySeo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache,DB,File,Log};
use Illuminate\Support\Sleep;

class LaptopNewsBatch extends Command {
 protected $signature='news:laptop-batch {--per-category=10 : Doel per nieuwscategorie, 1 tot 20} {--resume : Hervat alleen een bestaande ronde} {--new-batch : Begin na een afgeronde ronde een nieuwe} {--status : Toon alleen de voortgang}';
 protected $description='Vul een eenmalige nieuwsronde, met beelden via de laptop en automatisch hervatten';
 private array $state=[];
 private string $path;
 private float $deadline;
 private function save():void {File::ensureDirectoryExists(dirname($this->path));File::replace($this->path,json_encode($this->state,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));}
 private function counts(bool $reserved=false):array {
  $counts=array_fill_keys(NewsCategoryBalance::CATEGORIES,0);if(!$this->state)return $counts;
  $q=Article::whereIn('id',array_keys($this->state['selected']))->where('origin','news')->whereNull('hidden_at')->whereIn('status',$reserved?['news_ready','published']:['published']);
  foreach($q->get(['id','category']) as $a)if(isset($counts[$a->category]))$counts[$a->category]++;
  return $counts;
 }
 private function report():void {
  $published=$this->counts();$reserved=$this->counts(true);
  foreach($published as $category=>$count)$this->line(StorySeo::CATEGORIES[$category][0].': '.$count.'/'.$this->state['target'].' gepubliceerd, '.max(0,$reserved[$category]-$count).' wacht op beeld/controle.');
 }
 private function defer(AiWaitException $e):int {$this->state['retry_at']=$e->retryAt->toIso8601String();$this->save();$this->warn('AI-tegoed of dienst tijdelijk niet beschikbaar. Automatische volgende poging vanaf '.$e->retryAt->timezone('Europe/Amsterdam')->format('d-m H:i').'.');$this->report();return self::SUCCESS;}
 private function picture(Article $a,ArticlePhotoFinder $photos):void {
  if(app(NewsDuplicates::class)->hideIfDuplicate($a)){$a->save();unset($this->state['selected'][$a->id]);$this->save();return;}
  try {
   if(!$photos->current($a))$photos->attach($a);
   $a->refresh();if(!$photos->current($a))return;
   DB::transaction(function()use($a){$row=Article::whereKey($a->id)->lockForUpdate()->first();if($row&&$row->status==='news_ready'&&!$row->hidden_at)$row->update(['status'=>'published','published_at'=>$row->published_at??now()]);});
   $this->line('#'.$a->id.' gepubliceerd.');
  }catch(ImagePendingException $e){$this->line('#'.$a->id.' staat in de laptopwachtrij.');}
  catch(ArticleImageRejected $e){$a->update(['status'=>'draft','reason'=>'Twee beeldkandidaten onvoldoende passend; handmatige controle nodig.']);unset($this->state['selected'][$a->id]);$this->save();$this->warn('#'.$a->id.' wacht op handmatige beeldcontrole; een andere kandidaat vult de ronde.');}
 }
 public function handle(NewsImporter $importer,ArticlePhotoFinder $photos):int {
  $this->path=(string)config('laptop-images.news_batch_file',storage_path('app/laptop-news-batch.json'));
  if(!app(LaptopImages::class)->enabled()){$this->error('Deze opdracht vereist NEWS_IMAGE_PROVIDER=laptop en LAPTOP_IMAGES_ENABLED=true.');return self::FAILURE;}
  $raw=(string)$this->option('per-category');if(!ctype_digit($raw)||(int)$raw<1||(int)$raw>20){$this->error('--per-category moet tussen 1 en 20 liggen.');return self::INVALID;}
  $lock=Cache::lock('lichtpunt-import',300);if(!$lock->get()){$this->warn('Er loopt al een nieuwsimport. Deze ronde wordt later hervat.');return self::SUCCESS;}
  try {
   if(File::exists($this->path)){
    $saved=json_decode(File::get($this->path),true,512,JSON_THROW_ON_ERROR);
    if(!is_array($saved)||($saved['version']??null)!==1||!is_array($saved['selected']??null)||!is_int($saved['target']??null))throw new \RuntimeException('Ongeldige batchvoortgang.');$this->state=$saved;
   }
   if($this->option('status')){if($this->state)$this->report();else $this->info('Er is nog geen laptopnieuwsronde gestart.');return self::SUCCESS;}
   if($this->option('resume')&&(!$this->state||$this->state['finished']))return self::SUCCESS;
   if($this->option('new-batch')&&$this->state&&!$this->state['finished']){$this->error('Er staat nog een ronde open. Hervat met --resume.');return self::FAILURE;}
   if($this->state&&$this->state['finished']&&!$this->option('new-batch')){$this->info('Deze eenmalige ronde is afgerond. Een nieuwe ronde start alleen met --new-batch.');$this->report();return self::SUCCESS;}
   if(!$this->state||$this->option('new-batch')){
    if($this->state){File::ensureDirectoryExists(dirname($this->path).'/completed');File::replace(dirname($this->path).'/completed/laptop-batch-'.bin2hex(random_bytes(8)).'.json',json_encode($this->state,JSON_THROW_ON_ERROR));}
    $this->state=['version'=>1,'target'=>(int)$raw,'started_at'=>now()->toIso8601String(),'selected'=>[],'cursors'=>[],'exhausted'=>[],'visits'=>[],'finished'=>false,'retry_at'=>null];$this->save();
   }elseif(!$this->option('resume')&&$this->state['target']!==(int)$raw){$this->error('De open ronde heeft een ander doel. Gebruik --resume.');return self::FAILURE;}
   $this->info('Eenmalige ronde: '.$this->state['target'].' per categorie, '.($this->state['target']*6).' in totaal. Bestaande publicaties blijven behouden.');
   if($this->state['retry_at']&&\Carbon\CarbonImmutable::parse($this->state['retry_at'])->isFuture()){$this->warn('Wacht op het volgende AI-/bronmoment.');$this->report();return self::SUCCESS;}
   $this->state['retry_at']=null;$this->deadline=microtime(true)+max(10,min(120,(int)config('laptop-images.news_batch_seconds',75)));
   $steps=max(1,min(100,(int)config('laptop-images.news_batch_steps',12)));$seen=[];$assessed=false;
   for($step=0;$step<$steps&&microtime(true)<$this->deadline;$step++){
    $published=$this->counts();if(min($published)>=$this->state['target']){$this->state['finished']=true;$this->state['finished_at']=now()->toIso8601String();$this->save();$this->report();$this->info('Ronde compleet. Er wordt niet automatisch nog een ronde gestart.');return self::SUCCESS;}
    // Finish only reserved articles whose upload has returned; waiting images do not
    // trigger more generation requests or consume the remaining category slots again.
    $returned=Article::whereIn('id',array_keys($this->state['selected']))->whereNotIn('id',$seen)->where('status','news_ready')->whereNull('hidden_at')->oldest('id')->get()->first(function($a){return \App\Models\LaptopImageJob::where('target_type','article')->where('target_id',$a->id)->where('content_hash',app(LaptopImages::class)->hashArticle($a))->whereIn('status',['generated','completed','applied','rejected','failed','cancelled'])->exists();});
    if($returned){$seen[]=$returned->id;$this->picture($returned,$photos);continue;}
    // Recover a reservation saved immediately before a crash while planning an image.
    $unplanned=Article::whereIn('id',array_keys($this->state['selected']))->whereNotIn('id',$seen)->where('status','news_ready')->whereNull('hidden_at')->oldest('id')->get()->first(function($a){return !\App\Models\LaptopImageJob::where('target_type','article')->where('target_id',$a->id)->where('content_hash',app(LaptopImages::class)->hashArticle($a))->exists();});
    if($unplanned){$seen[]=$unplanned->id;$this->picture($unplanned,$photos);continue;}
    $reserved=$this->counts(true);$missing=array_keys(array_filter($reserved,fn($n)=>$n<$this->state['target']));
    if(!$missing)break;
    $ready=Article::where('origin','news')->whereNull('hidden_at')->where('status','news_ready')->whereIn('category',$missing)->whereNotIn('id',array_keys($this->state['selected']))->oldest('id')->first();
    if($ready){$ready->update(['photo'=>array_merge($ready->photo??[],['requested_style'=>LichtpuntIllustrationPrompt::PHOTO_STYLE])]);$this->state['selected'][(string)$ready->id]=$ready->category;$this->save();$seen[]=$ready->id;$this->picture($ready,$photos);continue;}
    $candidate=Article::where('origin','news')->whereNull('hidden_at')->whereIn('status',['pending','error'])->where('attempts','<',3)->where(fn($q)=>$q->whereIn('category',$missing)->orWhereNull('category'))->oldest('id')->first();
    if($candidate){app(AiAvailability::class)->check();if($assessed)Sleep::for(max(0,(int)config('news_hourly.assessment_delay',8)))->seconds();$assessed=true;$importer->assess($candidate,true,'news_ready');$this->line('#'.$candidate->id.' '.$candidate->fresh()->status);continue;}
    $sources=array_values(array_diff($missing,$this->state['exhausted']));
    if(!$sources){$this->state['retry_at']=now()->addHour()->toIso8601String();$this->state['exhausted']=[];$this->state['cursors']=[];$this->save();$this->warn('Nu onvoldoende geschikte bronartikelen. Over een uur opnieuw proberen.');break;}
    usort($sources,fn($a,$b)=>(($this->state['visits'][$a]??0)<=>($this->state['visits'][$b]??0))?:($reserved[$a]<=>$reserved[$b]));$cat=$sources[0];
    $page=$importer->fetchPage($this->state['cursors'][$cat]??null,$cat);$this->state['visits'][$cat]=($this->state['visits'][$cat]??0)+1;
    if(empty($page['next'])||$page['next']===($this->state['cursors'][$cat]??null))$this->state['exhausted'][]=$cat;$this->state['cursors'][$cat]=$page['next']??null;$this->save();$this->line(StorySeo::CATEGORIES[$cat][0].': '.count($page['created']).' nieuwe kandidaten opgehaald.');
   }
   if(min($this->counts())>=$this->state['target']){$this->state['finished']=true;$this->state['finished_at']=now()->toIso8601String();$this->save();}
   $this->report();$this->info($this->state['finished']?'Ronde compleet.':'Voortgang bewaard. De scheduler hervat deze ronde automatisch.');return self::SUCCESS;
  }catch(AiWaitException $e){return $this->defer($e);}
  catch(\Throwable $e){
   Log::warning('Laptop news batch paused',['exception'=>get_class($e)]);
   if($this->state){$this->state['retry_at']=now()->addMinutes(5)->toIso8601String();$this->save();$this->report();}
   $message=$e instanceof \App\Services\NewsServiceException||preg_match('/^(NewsData|Gemini) HTTP \d{3}\.$/',$e->getMessage())?$e->getMessage():'Controleer de bron-/AI-configuratie en het Laravel-logboek.';
   $this->warn('Ronde gepauzeerd. '.$message);return self::FAILURE;
  }finally{$lock->release();}
 }
}
