<?php
namespace App\Console\Commands;
use App\Models\Article;
use App\Services\{ArticlePhotoFinder,NewsImporter,NewsServiceException};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache,DB,File,Schema};
class ReplaceNewsCollection extends Command {
 protected $signature='news:replace {--pages=50 : Maximum extra nieuwspaginas deze run} {--restart-feed : Begin de nieuwsfeed opnieuw, behoud voorbereide artikelen}';
 protected $description='Bereid 100 nieuwe positieve artikelen met illustraties voor en vervang daarna de oude collectie';
 private array $state=[];
 private function path():string {return (string)config('news_images.replacement_state_file',storage_path('app/news-replacement-state.json'));}
 private function save():void {
  File::ensureDirectoryExists(dirname($this->path()));$tmp=$this->path().'.tmp';
  if(File::put($tmp,json_encode($this->state,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR))===false||!rename($tmp,$this->path()))throw new NewsServiceException('Vervangingsvoortgang opslaan mislukt.');
 }
 private function ready(){return Article::whereIn('id',$this->state['new_ids'])->where('status','replacement')->orderBy('id')->limit(100)->get();}
 public function handle(NewsImporter $importer,ArticlePhotoFinder $finder):int {
  $raw=(string)$this->option('pages');if(!ctype_digit($raw)||(int)$raw<1||(int)$raw>50){$this->error('--pages moet tussen 1 en 50 liggen.');return self::FAILURE;}
  $locks=[];
  try {
   foreach(['lichtpunt-import','lichtpunt-illustration-batch','lichtpunt-news-replacement'] as $name){$lock=Cache::lock($name,86400);if(!$lock->get())throw new NewsServiceException('Er loopt al een nieuws- of illustratietaak.');$locks[]=$lock;}
   if(is_file($this->path())){$this->state=json_decode(File::get($this->path()),true,512,JSON_THROW_ON_ERROR);if(!is_array($this->state['old_ids']??null)||!is_array($this->state['new_ids']??null))throw new NewsServiceException('Vervangingsvoortgang is ongeldig.');}
   else{$this->state=['old_ids'=>Article::where('origin','news')->pluck('id')->all(),'new_ids'=>[],'cursor'=>null,'exhausted'=>false,'finished'=>false];$this->save();}
   if(!empty($this->state['selected_ids'])&&count($this->state['selected_ids'])===100&&Article::whereIn('id',$this->state['selected_ids'])->where('status','published')->count()===100&&!Article::whereIn('id',$this->state['old_ids'])->where('origin','news')->exists()){$this->state['finished']=true;$this->save();}
   if($this->state['finished']){$this->info('Deze vervanging is afgerond: 100 nieuwe artikelen. Er wordt niets opnieuw verwijderd.');return self::SUCCESS;}
   if($this->option('restart-feed')){$this->state['cursor']=null;$this->state['exhausted']=false;$this->save();}
   $pages=0;
   while($this->ready()->count()<100){
    $queue=Article::whereIn('id',$this->state['new_ids'])->whereIn('status',['pending','error'])->where('attempts','<',3)->orderBy('id')->get();
    foreach($queue as $a){
     if($this->ready()->count()>=100)break;
     $this->line('#'.$a->id.' positief nieuws beoordelen');$importer->assess($a,true);$this->line('#'.$a->id.' '.$a->fresh()->status.' · '.$this->ready()->count().'/100 geschikt');
     if((int)config('news_images.assessment_delay_ms',8000)>0)usleep((int)config('news_images.assessment_delay_ms',8000)*1000);
    }
    if($this->ready()->count()>=100)break;
    if($pages>=(int)$raw||$this->state['exhausted']){$this->info($this->ready()->count().'/100 nieuwe positieve artikelen klaar. Oude collectie blijft staan. Hervat met news:replace; gebruik --restart-feed als de feed klaar/verlopen is.');return self::SUCCESS;}
    $page=$importer->fetchPage($this->state['cursor']);$pages++;
    $this->state['new_ids']=array_values(array_unique(array_merge($this->state['new_ids'],$page['created'])));
    if(!$page['next']||$page['next']===$this->state['cursor'])$this->state['exhausted']=true;
    $this->state['cursor']=$page['next'];$this->save();$this->line(count($page['created']).' nieuwe kandidaten opgehaald.');
   }
   $ready=$this->ready();$done=0;
   foreach($ready as $a){if($finder->current($a)){$done++;continue;}$this->line('#'.$a->id.' Lichtpuntjes-illustratie maken · '.($done+1).'/100');$finder->attach($a);$done++;}
   $this->commit($ready->pluck('id')->all(),$finder);
   $this->state['finished']=true;$this->save();$this->info('Klaar: 100 nieuwe positieve artikelen met illustraties gepubliceerd. Oude artikelen verwijderd. Herstelkopie: '.$this->state['backup']);return self::SUCCESS;
  }catch(\Throwable $e){$message=$e instanceof NewsServiceException||get_class($e)===\RuntimeException::class&&preg_match('/^(NewsData |Gemini |AI-formaatfout: |NEWSDATA_API_KEY |GEMINI_API_KEY |Vertaling ontbreekt|Ongeldige modelnaam)/',$e->getMessage())?$e->getMessage():'Vervangen mislukt. Controleer database, verbinding en schrijfrechten.';$this->error($message);$this->warn('Oude collectie blijft behouden totdat de nieuwe 100 compleet zijn. Hervat met dezelfde opdracht.');return self::FAILURE;}
  finally{foreach(array_reverse($locks) as $lock)$lock->release();}
 }
 private function commit(array $ids,ArticlePhotoFinder $finder):void {
  DB::transaction(function()use($ids,$finder){
   $new=Article::whereIn('id',$ids)->lockForUpdate()->get();
   if(count($ids)!==100||$new->count()!==100||$new->where('status','replacement')->count()!==100)throw new NewsServiceException('De nieuwe collectie is niet compleet. Niets verwijderd.');
   foreach($new as $a)if(!$finder->current($a))throw new NewsServiceException('Een nieuwe illustratie ontbreekt. Niets verwijderd.');
   $remove=Article::whereNotIn('id',$ids)->where('origin','news')->pluck('id')->all();
   $rows=['articles'=>DB::table('articles')->whereIn('id',$remove)->get()->toArray()];
   foreach(['article_reads','article_likes','user_article_likes','comments'] as $table)if(Schema::hasTable($table))$rows[$table]=DB::table($table)->whereIn('article_id',$remove)->get()->toArray();
   if(Schema::hasTable('comment_reviews'))$rows['comment_reviews']=DB::table('comment_reviews')->whereIn('comment_id',array_map(fn($r)=>$r->id,$rows['comments']??[]))->get()->toArray();
   File::ensureDirectoryExists(storage_path('app/news-replacement-backups'));
   $backup='storage/app/news-replacement-backups/'.now()->format('Ymd-His').'-'.\Illuminate\Support\Str::uuid().'.json';
   if(File::put(base_path($backup),json_encode(['created_at'=>now()->toIso8601String(),'tables'=>$rows,'new_ids'=>$ids],JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT))===false)throw new NewsServiceException('Herstelkopie opslaan mislukt. Niets verwijderd.');
   $this->state['backup']=$backup;$this->state['selected_ids']=$ids;$this->save();
   Article::whereIn('id',$ids)->update(['status'=>'published']);Article::whereIn('id',$remove)->delete();
  });
 }
}
