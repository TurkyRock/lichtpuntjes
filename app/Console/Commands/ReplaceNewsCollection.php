<?php
namespace App\Console\Commands;
use App\Models\Article;
use App\Services\{ArticlePhotoFinder,NewsImporter,NewsServiceException};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache,DB,File,Schema};
class ReplaceNewsCollection extends Command {
 protected $signature='news:replace {--count=100 : Aantal nieuwe berichten, 6 tot 100} {--photorealistic : Afzonderlijke proefbatch met fotorealistische sfeerbeelden} {--pages=50 : Maximum extra nieuwspaginas deze run} {--restart-feed : Begin de nieuwsfeed opnieuw, behoud voorbereide artikelen} {--publish-ready : Publiceer alvast goedgekeurde batchberichten met een complete illustratie, zonder API-aanvragen}';
 protected $description='Bereid 100 nieuwe positieve artikelen met illustraties voor en vervang daarna de oude collectie';
 private array $state=[];
 private int $target=100;
 private bool $photorealistic=false;
 private function path():string {if($this->photorealistic)return (string)config('news_images.photo_trial_state_file',storage_path('app/news-photo-trial-state.json'));return (string)config('news_images.replacement_state_file',storage_path('app/news-replacement-state.json'));}
 private function save():void {
  File::ensureDirectoryExists(dirname($this->path()));$tmp=$this->path().'.tmp';
  if(File::put($tmp,json_encode($this->state,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR))===false||!rename($tmp,$this->path()))throw new NewsServiceException('Vervangingsvoortgang opslaan mislukt.');
 }
 private function ready(){
  $query=Article::whereNull('hidden_at')->whereIn('id',$this->state['new_ids'])->whereIn('status',['replacement','published'])->orderBy('id');
  if(empty($this->state['category_balanced']))return $query->limit($this->target)->get();
  $ready=$query->get();$selected=new \Illuminate\Database\Eloquent\Collection();
  foreach(\App\Services\NewsCategoryBalance::CATEGORIES as $i=>$category)$selected=$selected->merge($ready->where('category',$category)->take(intdiv($this->target,6)+($i<$this->target%6?1:0)));
  return $selected;
 }
 private function fetchCategory():string {
  $counts=Article::whereNull('hidden_at')->whereIn('id',$this->state['new_ids'])->whereIn('status',['pending','photo_trial_pending','error','replacement','published'])->select('category')->selectRaw('count(*) as total')->groupBy('category')->pluck('total','category');
  $cats=\App\Services\NewsCategoryBalance::CATEGORIES;
  usort($cats,fn($a,$b)=>(($counts[$a]??0)+10*($this->state['topic_visits'][$a]??0))<=>(($counts[$b]??0)+10*($this->state['topic_visits'][$b]??0)));
  return $cats[0];
 }
 public function handle(NewsImporter $importer,ArticlePhotoFinder $finder):int {
  $count=(string)$this->option('count');if(!ctype_digit($count)||(int)$count<6||(int)$count>100){$this->error('--count moet tussen 6 en 100 liggen.');return self::FAILURE;}
  $this->target=(int)$count;$this->photorealistic=(bool)$this->option('photorealistic');
  if($this->photorealistic&&$this->option('publish-ready')){$this->error('De fotoproef wordt pas gepubliceerd als de hele batch klaar is. Gebruik news:photo-trial.');return self::FAILURE;}
  $raw=(string)$this->option('pages');if(!ctype_digit($raw)||(int)$raw<1||(int)$raw>50){$this->error('--pages moet tussen 1 en 50 liggen.');return self::FAILURE;}
  $locks=[];
  try {
   foreach(['lichtpunt-import','lichtpunt-illustration-batch','lichtpunt-news-replacement'] as $name){$lock=Cache::lock($name,86400);if(!$lock->get())throw new NewsServiceException('Er loopt al een nieuws- of illustratietaak.');$locks[]=$lock;}
   if(is_file($this->path())){$this->state=json_decode(File::get($this->path()),true,512,JSON_THROW_ON_ERROR);if(!is_array($this->state['old_ids']??null)||!is_array($this->state['new_ids']??null)||($this->state['target']??100)!==$this->target||($this->state['photorealistic']??false)!==$this->photorealistic)throw new NewsServiceException('Vervangingsvoortgang is ongeldig.');}
   else{if($this->option('publish-ready')){$this->warn('Geen bestaande vervangingsbatch gevonden. Er is niets gepubliceerd.');return self::SUCCESS;}$this->state=['target'=>$this->target,'photorealistic'=>$this->photorealistic,'old_ids'=>Article::where('origin','news')->when($this->photorealistic,fn($q)=>$q->where('status','published')->whereNull('hidden_at'))->pluck('id')->all(),'new_ids'=>[],'cursor'=>null,'exhausted'=>false,'finished'=>false,'category_balanced'=>true,'topic_cursors'=>[],'topic_visits'=>[]];$this->save();}
   if(!empty($this->state['selected_ids'])&&count($this->state['selected_ids'])===$this->target&&Article::whereIn('id',$this->state['selected_ids'])->where('status','published')->count()===$this->target&&!Article::whereIn('id',$this->state['old_ids'])->where('origin','news')->whereNull('hidden_at')->exists()){$this->state['finished']=true;$this->save();}
   if($this->state['finished']){$this->info($this->target===100?'Deze vervanging is afgerond: 100 nieuwe artikelen. Er wordt niets opnieuw verwijderd.':'Deze proefbatch is afgerond: '.$this->target.' nieuwe artikelen. Er wordt niets opnieuw vervangen.');return self::SUCCESS;}
   if($this->option('publish-ready')){$this->publishReady($finder);return self::SUCCESS;}
   if($this->option('restart-feed')){$this->state['cursor']=null;$this->state['topic_cursors']=[];$this->state['topic_visits']=[];$this->state['exhausted']=false;$this->save();}
   $pages=0;
   while($this->ready()->count()<$this->target){
    $queue=Article::whereNull('hidden_at')->whereIn('id',$this->state['new_ids'])->whereIn('status',$this->photorealistic?['photo_trial_pending','error']:['pending','error'])->where('attempts','<',3)->orderBy('id')->get();
    foreach($queue as $a){
     if($this->ready()->count()>=$this->target)break;
     $this->line('#'.$a->id.' positief nieuws beoordelen');$importer->assess($a,true);$this->line('#'.$a->id.' '.$a->fresh()->status.' · '.$this->ready()->count().'/'.$this->target.' geschikt');
     if((int)config('news_images.assessment_delay_ms',8000)>0)usleep((int)config('news_images.assessment_delay_ms',8000)*1000);
    }
    if($this->ready()->count()>=$this->target)break;
    if($pages>=(int)$raw||$this->state['exhausted']){$this->info($this->ready()->count().'/'.$this->target.' nieuwe positieve artikelen klaar. Oude collectie blijft staan. Hervat met '.($this->photorealistic?'news:photo-trial':'news:replace').'; gebruik --restart-feed als de feed klaar/verlopen is.');return self::SUCCESS;}
    if(!empty($this->state['category_balanced'])){
     $topic=$this->fetchCategory();$cursor=$this->state['topic_cursors'][$topic]??null;
     $page=$importer->fetchPage($cursor,$topic);
     $this->state['topic_cursors'][$topic]=$page['next'];
     $this->state['topic_visits'][$topic]=($this->state['topic_visits'][$topic]??0)+1;
    }else{$page=$importer->fetchPage($this->state['cursor']);}
    if($this->photorealistic&&$page['created'])Article::whereIn('id',$page['created'])->where('status','pending')->update(['status'=>'photo_trial_pending']);
    $pages++;
    $this->state['new_ids']=array_values(array_unique(array_merge($this->state['new_ids'],$page['created'])));
    if(empty($this->state['category_balanced'])&&(!$page['next']||$page['next']===$this->state['cursor']))$this->state['exhausted']=true;
    $this->state['cursor']=$page['next'];$this->save();$this->line(count($page['created']).' nieuwe kandidaten opgehaald.');
   }
   $ready=$this->ready();$done=0;
   foreach($ready as $a){if($finder->current($a)&&(!$this->photorealistic||($a->photo['style']??'')===\App\Services\LichtpuntIllustrationPrompt::PHOTO_STYLE)){$done++;continue;}$this->line('#'.$a->id.($this->photorealistic?' Fotorealistisch sfeerbeeld maken · ':' Lichtpuntjes-illustratie maken · ').($done+1).'/'.$this->target);if($this->photorealistic){$photo=$a->photo??[];$photo['requested_style']=\App\Services\LichtpuntIllustrationPrompt::PHOTO_STYLE;$a->update(['photo'=>$photo]);}$finder->attach($a);$done++;}
   $this->commit($ready->pluck('id')->all(),$finder);
   $this->state['finished']=true;$this->save();$this->info('Klaar: '.$this->target.' nieuwe positieve artikelen met '.($this->photorealistic?'fotorealistische sfeerbeelden gepubliceerd. Oude nieuwsartikelen uit het openbare overzicht gehaald. Herstelkopie: ':'illustraties gepubliceerd. Oude artikelen verwijderd. Herstelkopie: ').$this->state['backup']);return self::SUCCESS;
  }catch(\Throwable $e){$message=$e instanceof NewsServiceException||get_class($e)===\RuntimeException::class&&preg_match('/^(NewsData |Gemini |AI-formaatfout: |NEWSDATA_API_KEY |GEMINI_API_KEY |Vertaling ontbreekt|Ongeldige modelnaam)/',$e->getMessage())?$e->getMessage():'Vervangen mislukt. Controleer database, verbinding en schrijfrechten.';$this->error($message);$this->warn('Oude collectie blijft behouden totdat de nieuwe '.$this->target.' compleet zijn. Hervat met dezelfde opdracht.');return self::FAILURE;}
  finally{foreach(array_reverse($locks) as $lock)$lock->release();}
 }
 private function publishReady(ArticlePhotoFinder $finder):void {
  $ids=DB::transaction(function()use($finder){
   $articles=Article::whereNull('hidden_at')->whereIn('id',$this->ready()->pluck('id')->all())->where('origin','news')->where('status','replacement')->lockForUpdate()->orderBy('id')->get();
   $ids=[];
   foreach($articles as $article){
    $valid=true;
    foreach(['nl','en'] as $language){$text=$article->{$language};if(!is_array($text)||!is_string($text['title']??null)||trim($text['title'])===''||!is_array($text['body']??null)||count($text['body'])===0||count(array_filter($text['body'],'is_string'))!==count($text['body'])){$valid=false;break;}}
    if($valid&&$finder->current($article))$ids[]=$article->id;
   }
   if($ids)Article::whereIn('id',$ids)->update(['status'=>'published']);
   return $ids;
  });
  $this->state['published_ids']=array_values(array_unique(array_merge($this->state['published_ids']??[],$ids)));$this->save();
  $total=Article::whereNull('hidden_at')->whereIn('id',$this->state['new_ids'])->where('origin','news')->where('status','published')->count();
  $this->info(count($ids).' nieuwe berichten alvast gepubliceerd. '.$total.'/'.$this->target.' batchberichten staan nu online.');
  $this->info('Geen nieuwe AI-aanvragen gedaan. Oude collectie blijft staan. Hervat later met php artisan news:replace om de batch af te maken.');
 }
 private function commit(array $ids,ArticlePhotoFinder $finder):void {
  DB::transaction(function()use($ids,$finder){
   $new=Article::whereIn('id',$ids)->lockForUpdate()->get();
   if(count($ids)!==$this->target||$new->count()!==$this->target||$new->whereIn('status',['replacement','published'])->count()!==$this->target)throw new NewsServiceException('De nieuwe collectie is niet compleet. Niets verwijderd.');
   foreach($new as $a)if(!$finder->current($a)||($this->photorealistic&&($a->photo['style']??'')!==\App\Services\LichtpuntIllustrationPrompt::PHOTO_STYLE))throw new NewsServiceException('Een nieuwe illustratie ontbreekt. Niets verwijderd.');
   $removeQuery=Article::whereNotIn('id',$ids)->where('origin','news')->whereNull('hidden_at');
   if($this->photorealistic)$removeQuery->where('status','published');
   $remove=$removeQuery->pluck('id')->all();
   $rows=['articles'=>DB::table('articles')->whereIn('id',$remove)->get()->toArray()];
   foreach(['article_reads','article_likes','user_article_likes','comments'] as $table)if(Schema::hasTable($table))$rows[$table]=DB::table($table)->whereIn('article_id',$remove)->get()->toArray();
   if(Schema::hasTable('comment_reviews'))$rows['comment_reviews']=DB::table('comment_reviews')->whereIn('comment_id',array_map(fn($r)=>$r->id,$rows['comments']??[]))->get()->toArray();
   File::ensureDirectoryExists(storage_path('app/news-replacement-backups'));
   $backup='storage/app/news-replacement-backups/'.now()->format('Ymd-His').'-'.\Illuminate\Support\Str::uuid().'.json';
   if(File::put(base_path($backup),json_encode(['created_at'=>now()->toIso8601String(),'tables'=>$rows,'new_ids'=>$ids],JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT))===false)throw new NewsServiceException('Herstelkopie opslaan mislukt. Niets verwijderd.');
   $this->state['backup']=$backup;$this->state['selected_ids']=$ids;$this->save();
   Article::whereIn('id',$ids)->update(['status'=>'published']);if($this->photorealistic)Article::whereIn('id',$remove)->update(['hidden_at'=>now(),'hidden_by'=>null]);else Article::whereIn('id',$remove)->delete();
  });
 }
}
