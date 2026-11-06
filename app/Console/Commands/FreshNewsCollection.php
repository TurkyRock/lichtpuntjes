<?php
namespace App\Console\Commands;

use App\Models\Article;
use App\Services\{ArticleImageGenerator,ArticleImageRejected,ArticlePhotoFinder,LichtpuntIllustrationPrompt,NewsAssessmentRetry,NewsCategoryBalance,NewsImporter,NewsServiceException};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache,DB,File,Log,Schema};

class FreshNewsCollection extends Command
{
    protected $signature='news:fresh {--new-batch : Start bewust een nieuwe collectie; bewaar eerst de vorige voortgang en herstelkopie} {--count=20 : Aantal nieuwe nieuwsartikelen, 6 tot 100} {--pages=50 : Maximum bronpaginas per uitvoering} {--restart-feed : Herstart alleen de bronpaginering, behoud voorbereide artikelen}';
    protected $description='Wis alle oude nieuwsartikelen inclusief archief en bouw een hervatbare nieuwe collectie met fotorealistische sfeerbeelden';
    private array $state=[];
    private int $target=20;
    private ?int $activeArticle=null;
    private string $phase='voorbereiden';
    private function path():string {return (string)config('news_images.fresh_state_file',storage_path('app/news-fresh-collection.json'));}
    private function save():void {
        File::ensureDirectoryExists(dirname($this->path()));$tmp=$this->path().'.tmp';
        $json=json_encode($this->state,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
        if(File::put($tmp,$json)!==strlen($json)||!rename($tmp,$this->path()))throw new NewsServiceException('Voortgang schone nieuwscollectie opslaan mislukt.');
    }
    private function oldBackup():void {
        DB::transaction(function(){
            $ids=$this->state['old_ids'];$articles=Article::where('origin','news')->whereIn('id',$ids)->lockForUpdate()->get();
            if(empty($this->state['backup'])){
                $tables=['articles'=>$articles->map(fn($a)=>$a->getRawOriginal())->all()];
                foreach(['article_reads','article_likes','user_article_likes','comments','daily_spotlights'] as $table)if(Schema::hasTable($table))$tables[$table]=DB::table($table)->whereIn('article_id',$ids)->get()->toArray();
                $commentIds=array_map(fn($c)=>$c->id,$tables['comments']??[]);
                foreach(['comment_reviews','comment_hearts'] as $table)if(Schema::hasTable($table))$tables[$table]=DB::table($table)->whereIn('comment_id',$commentIds)->get()->toArray();
                $dir=storage_path('app/news-replacement-backups');File::ensureDirectoryExists($dir);$path=$dir.'/fresh-'.\Illuminate\Support\Str::uuid().'.json';
                $json=json_encode(['created_at'=>now()->toIso8601String(),'tables'=>$tables],JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);
                if(File::put($path.'.tmp',$json)!==strlen($json)||!rename($path.'.tmp',$path))throw new NewsServiceException('Herstelkopie opslaan mislukt. Er is niets verwijderd.');
                $this->state['backup']=$path;$this->state['backup_sha256']=hash('sha256',$json);$this->save();
            }
            if(!is_file($this->state['backup'])||hash_file('sha256',$this->state['backup'])!==($this->state['backup_sha256']??null))throw new NewsServiceException('Herstelkopie ontbreekt. Er is niets verwijderd.');
            Article::where('origin','news')->whereIn('id',$ids)->delete();
        });
        $this->state['cleared']=true;$this->save();
        $this->info('Oude nieuwscollectie en archief verwijderd. Herstelkopie: '.$this->state['backup']);
    }
    private function ready(ArticlePhotoFinder $finder):array {
        $ids=[];
        foreach(Article::where('origin','news')->whereIn('id',$this->state['ready_ids'])->whereNull('hidden_at')->whereIn('status',['replacement','published'])->get() as $a)
            if($finder->current($a)&&($a->photo['style']??null)===LichtpuntIllustrationPrompt::PHOTO_STYLE)$ids[]=$a->id;
        return $ids;
    }
    private function atQuota(string $category):bool {
        $index=array_search($category,NewsCategoryBalance::CATEGORIES,true);if($index===false)return true;
        $max=intdiv($this->target,6)+($index<$this->target%6?1:0);
        return Article::whereIn('id',$this->state['ready_ids'])->where('category',$category)->count()>=$max;
    }
    private function process(NewsImporter $importer,ArticlePhotoFinder $finder):void {
        foreach(Article::where('origin','news')->whereIn('id',$this->state['new_ids'])->whereNotIn('id',array_keys($this->state['image_rejections']))->whereNull('hidden_at')->orderBy('id')->get() as $a){
            $this->activeArticle=$a->id;
            if(count($this->state['ready_ids'])>=$this->target)break;
            if(in_array($a->id,$this->state['ready_ids'],true)||$this->atQuota((string)$a->category))continue;
            if(in_array($a->status,['pending','photo_trial_pending','error'],true)&&$a->attempts<3){
                $delay=max(0,(int)config('news_images.assessment_delay_ms',8000));if($delay)\Illuminate\Support\Sleep::for($delay)->milliseconds();
                $this->phase='positief nieuws beoordelen';
                $this->line('#'.$a->id.' positief nieuws beoordelen');app(NewsAssessmentRetry::class)->assess($importer,$a,true,fn($m)=>$this->warn($m));$a->refresh();
            }
            if($a->status!=='replacement'||$a->hidden_at||$this->atQuota((string)$a->category))continue;
            $this->phase='fotorealistisch beeld maken';
            $this->line('#'.$a->id.' fotorealistisch beeld maken: '.($a->nl['title']??$a->source_title));
            $a->update(['photo'=>array_merge($a->photo??[],['requested_style'=>LichtpuntIllustrationPrompt::PHOTO_STYLE])]);
            try {$finder->attach($a);}
            catch(ArticleImageRejected $e){$this->state['image_rejections'][(string)$a->id]=$e->getMessage();$this->save();$this->warn('#'.$a->id.' '.$e->getMessage().' Ander artikel zoeken.');continue;}
            $a->refresh();if(!$finder->current($a))throw new NewsServiceException('Het nieuwe beeld is niet compleet opgeslagen. Hervat dezelfde opdracht.');
            $this->state['ready_ids'][]=$a->id;$this->save();$this->info(count($this->state['ready_ids']).'/'.$this->target.' nieuwe artikelen met beeld klaar.');
        }
    }
    private function topic():?string {
        $topics=array_values(array_filter(array_diff(NewsCategoryBalance::CATEGORIES,$this->state['exhausted_topics']),fn($c)=>!$this->atQuota($c)));if(!$topics)return null;
        $counts=Article::whereIn('id',$this->state['ready_ids'])->select('category')->selectRaw('count(*) as total')->groupBy('category')->pluck('total','category');
        usort($topics,fn($a,$b)=>(($counts[$a]??0)*10+($this->state['visits'][$a]??0))<=>(($counts[$b]??0)*10+($this->state['visits'][$b]??0)));
        return $topics[0];
    }
    private function publish(ArticlePhotoFinder $finder):void {
        DB::transaction(function()use($finder){
            $ids=$this->ready($finder);if(count($ids)!==$this->target)throw new NewsServiceException('De nieuwe collectie is nog niet compleet.');
            // All old news was backed up and deleted before fetching. Remove only unused
            // candidates from THIS run; never delete unrelated community contributions.
            Article::where('origin','news')->whereIn('id',$this->state['new_ids'])->whereNotIn('id',$ids)->delete();
            Article::where('origin','news')->whereIn('id',$ids)->update(['status'=>'published']);
        });
        $this->state['finished']=true;$this->save();$this->info('Klaar: '.$this->target.' nieuwe nieuwsartikelen met passende fotorealistische sfeerbeelden gepubliceerd.');
    }
    private function failureMessage(\Throwable $e):string {
        if($e instanceof NewsServiceException)return $e->getMessage();
        // SQL, HTTP request payloads and provider keys do not belong in console output or logs.
        if($e instanceof \Illuminate\Database\QueryException){
            $code=$e->errorInfo[0]??$e->getCode();
            return 'Databasefout (SQLSTATE '.preg_replace('/[^A-Z0-9]/','',(string)$code).'). Controleer de databaseverbinding en migraties.';
        }
        $text=$e->getMessage();
        if(preg_match('/^(Gemini|NewsData) HTTP ([0-9]{3})\.$/',$text,$match)){
            $hint=match((int)$match[2]){429=>'Quota of snelheidslimiet bereikt; probeer later opnieuw.',401,403=>'Controleer de API-sleutel en toegang.',404=>'Controleer de ingestelde modelnaam of API-route.',default=>'De provider kon het verzoek niet verwerken.'};
            return $text.' '.$hint;
        }
        if(str_starts_with($text,'AI-formaatfout:'))return 'Gemini-antwoord heeft niet het verwachte formaat. Hervat om dit artikel opnieuw te beoordelen.';
        if(in_array($text,['Gemini gaf geen complete samenvatting.','Gemini gaf ongeldige JSON.','Gemini gaf geen object.','Vertaling ontbreekt.','NewsData verbinding mislukt.','NewsData gaf geen geldige resultaten.','NEWSDATA_API_KEY ontbreekt.','GEMINI_API_KEY ontbreekt.','Ongeldige modelnaam.'],true))return $text;
        return 'Onverwachte fout: '.class_basename($e).' in '.basename($e->getFile()).':'.$e->getLine().'. Zie het Laravel-logboek voor de stap en het artikelnummer.';
    }
    public function handle(NewsImporter $importer,ArticlePhotoFinder $finder,ArticleImageGenerator $images):int {
        $count=(string)$this->option('count');$pages=(string)$this->option('pages');
        if(!ctype_digit($count)||(int)$count<6||(int)$count>100||!ctype_digit($pages)||(int)$pages<1||(int)$pages>50){$this->error('--count: 6 tot 100; --pages: 1 tot 50.');return self::FAILURE;}
        $this->target=(int)$count;$locks=[];
        try {
            foreach(['lichtpunt-import','lichtpunt-illustration-batch','lichtpunt-news-replacement'] as $name){$lock=Cache::lock($name,86400);if(!$lock->get())throw new NewsServiceException('Er loopt al een nieuws- of illustratietaak.');$locks[]=$lock;}
            if(is_file($this->path())){
                $this->state=json_decode(File::get($this->path()),true,512,JSON_THROW_ON_ERROR);
                if(($this->state['version']??null)!==1||($this->state['target']??null)!==$this->target||!is_array($this->state['old_ids']??null)||!is_array($this->state['new_ids']??null)||!is_array($this->state['ready_ids']??null)||!is_array($this->state['image_rejections']??null))throw new NewsServiceException('Voortgang schone collectie ongeldig of ander aantal gekozen. Er is niets opnieuw verwijderd.');
                if(($this->state['finished']??false)&&!$this->option('new-batch')){$this->info('Deze schone collectie is klaar: '.$this->target.' artikelen. Er is niets opnieuw verwijderd.');return self::SUCCESS;}
            }
            $images->check();
            if(!config('news.newsdata_key')||!config('news.gemini_key'))throw new NewsServiceException('NEWSDATA_API_KEY en GEMINI_API_KEY moeten ingesteld zijn. Er is niets verwijderd.');
            if($this->option('new-batch')&&$this->state){
                $dir=storage_path('app/news-fresh-runs');File::ensureDirectoryExists($dir);
                $archived=$dir.'/previous-'.\Illuminate\Support\Str::uuid().'.json';
                $json=json_encode($this->state,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
                if(File::put($archived,$json)!==strlen($json))throw new NewsServiceException('Vorige voortgang bewaren mislukt. Er is niets opnieuw verwijderd.');
                $this->state=[];$this->info('Nieuwe batch gestart. Vorige voortgang bewaard: '.$archived);
            }
            if(!$this->state){$this->state=['version'=>1,'target'=>$this->target,'old_ids'=>Article::where('origin','news')->pluck('id')->all(),'new_ids'=>[],'ready_ids'=>[],'image_rejections'=>[],'cleared'=>false,'finished'=>false,'cursors'=>[],'visits'=>[],'exhausted_topics'=>[]];$this->save();}
            if(!$this->state['cleared'])$this->oldBackup();
            // A style change retries old image rejections, without clearing the news or backup again.
            if(($this->state['image_style']??null)!==LichtpuntIllustrationPrompt::PHOTO_STYLE){
                $this->state['image_style']=LichtpuntIllustrationPrompt::PHOTO_STYLE;
                $this->state['image_rejections']=[];
                $this->info('Nieuwe beeldstijl: fotorealistisch. Eerdere afkeuringen op papierstijl worden opnieuw geprobeerd.');
            }
            $this->state['ready_ids']=$this->ready($finder);$this->save();
            if($this->option('restart-feed')){$this->state['cursors']=[];$this->state['visits']=[];$this->state['exhausted_topics']=[];$this->save();}
            for($fetched=0;;){
                $this->process($importer,$finder);
                if(count($this->state['ready_ids'])===$this->target){$this->publish($finder);return self::SUCCESS;}
                $topic=$this->topic();
                if($fetched>=(int)$pages||$topic===null){$this->info(count($this->state['ready_ids']).'/'.$this->target.' klaar. Hervat met dezelfde opdracht. Gebruik --restart-feed als de bronpaginas op zijn.');return self::SUCCESS;}
                $this->activeArticle=null;$this->phase='nieuws ophalen';
                $cursor=$this->state['cursors'][$topic]??null;$page=$importer->fetchPage($cursor,$topic);$fetched++;
                $created=$page['created']??[];Article::where('origin','news')->whereIn('id',$created)->where('status','pending')->update(['status'=>'photo_trial_pending']);
                $this->state['new_ids']=array_values(array_unique(array_merge($this->state['new_ids'],$created)));
                $next=$page['next']??null;$this->state['cursors'][$topic]=$next;$this->state['visits'][$topic]=($this->state['visits'][$topic]??0)+1;
                if(!$next||$next===$cursor)$this->state['exhausted_topics'][]=$topic;
                $this->save();$this->line(count($created).' nieuwe kandidaten opgehaald voor '.$topic.'.');
            }
        }catch(\Throwable $e){
            $message=$this->failureMessage($e);
            $where=$this->activeArticle?'#'.$this->activeArticle.' · '.$this->phase:$this->phase;
            try{Log::error('Schone nieuwsimport onderbroken',['diagnose'=>$message,'stap'=>$this->phase,'artikel_id'=>$this->activeArticle,'type'=>$e::class,'bestand'=>basename($e->getFile()),'regel'=>$e->getLine()]);}
            catch(\Throwable){$this->warn('Foutlog kon niet worden opgeslagen; de melding hieronder blijft beschikbaar.');}
            $this->error($where.': '.$message);$this->warn('Voorbereide artikelen en beelden blijven bewaard. Hervat met dezelfde opdracht.');return self::FAILURE;
        }finally{foreach(array_reverse($locks) as $lock)$lock->release();}
    }
}
