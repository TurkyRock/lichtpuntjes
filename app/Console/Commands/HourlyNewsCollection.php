<?php
namespace App\Console\Commands;

use App\Models\Article;
use App\Services\{ArticlePhotoFinder,GeminiTemporaryException,ImageBudgetException,LichtpuntIllustrationPrompt,NewsCategoryBalance,NewsDuplicates,NewsImporter};
use App\Support\StorySeo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache,DB,File,Log};
use Illuminate\Support\Sleep;

class HourlyNewsCollection extends Command
{
    protected $signature='news:hourly {--per-category= : Nieuwe artikelen per nieuwscategorie; standaard 3}';
    protected $description='Vul een hervatbare uurronde met drie nieuwe, gecontroleerde artikelen met beeld per nieuwscategorie';
    private array $state=[];
    private float $deadline;
    private string $path;
    private int $target;

    private function save():void {
        File::ensureDirectoryExists(dirname($this->path));
        File::replace($this->path,json_encode($this->state,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
    }
    private function counts():array {
        $counts=array_fill_keys(NewsCategoryBalance::CATEGORIES,0);
        if(!$this->state['selected'])return $counts;
        $actual=Article::whereIn('id',array_keys($this->state['selected']))->where('origin','news')->whereNull('hidden_at')->where('status','published')
            ->select('category')->selectRaw('count(*) as total')->groupBy('category')->pluck('total','category');
        foreach($counts as $cat=>$_)$counts[$cat]=(int)($actual[$cat]??0);
        return $counts;
    }
    private function report():void {
        foreach($this->counts() as $cat=>$count)$this->line(StorySeo::CATEGORIES[$cat][0].': '.$count.'/'.$this->target.' nieuw.');
    }
    private function paused(string $reason,int $exit=2):int {
        $this->warn($reason);$this->report();
        $this->line('Voortgang bewaard. Hervat met: php -d memory_limit=512M artisan news:hourly --per-category='.$this->target);
        return $exit;
    }
    private function assess(NewsImporter $importer,Article $article):void {
        $retry=0;
        while(true){
            try{$importer->assess($article,true,'news_ready');return;}
            catch(GeminiTemporaryException $e){
                $seconds=$retry++===0?60:300;
                $this->warn('Gemini tijdelijk niet beschikbaar; nieuwe poging over '.$seconds.' seconden.');
                for($elapsed=0;$elapsed<$seconds;$elapsed+=5){
                    if(microtime(true)+5>=$this->deadline)throw new \RuntimeException('De uitvoeringstijd van deze ronde is voorbij.');
                    Sleep::for(5)->seconds();
                }
                $article->refresh();if($article->hidden_at)return;
            }
        }
    }
    public function handle(NewsImporter $importer,ArticlePhotoFinder $photos):int {
        $raw=(string)($this->option('per-category')??config('news_hourly.per_category',3));
        if(!ctype_digit($raw)||(int)$raw<1||(int)$raw>20){$this->error('--per-category moet tussen 1 en 20 liggen.');return self::INVALID;}
        $this->target=(int)$raw;$this->path=(string)config('news_hourly.state_file');
        $seconds=max(60,(int)config('news_hourly.max_seconds',3300));$this->deadline=microtime(true)+$seconds;
        $lock=Cache::lock('lichtpunt-import',$seconds+900);
        if(!$lock->get()){$this->warn('Er loopt al een import. Deze uurronde wordt niet tegelijk gestart.');return self::SUCCESS;}
        try {
            if(File::exists($this->path)){
                $saved=json_decode(File::get($this->path),true,512,JSON_THROW_ON_ERROR);
                if(!is_array($saved)||($saved['version']??null)!==1||!is_array($saved['selected']??null)||!is_int($saved['target']??null)||!is_string($saved['hour']??null))throw new \RuntimeException('Ongeldige voortgang.');
                $this->state=$saved;
            }
            $hour=now('UTC')->format('Y-m-d-H');
            if($this->state&&($this->state['finished']??false)&&$this->state['hour']===$hour){$this->info('Deze uurronde is al afgerond. Er worden geen extra artikelen geplaatst.');return self::SUCCESS;}
            if(!$this->state||($this->state['finished']??false)){
                if($this->state){File::ensureDirectoryExists(dirname($this->path).'/completed');File::replace(dirname($this->path).'/completed/'.$this->state['hour'].'.json',json_encode($this->state,JSON_THROW_ON_ERROR));}
                $this->state=['version'=>1,'hour'=>$hour,'started_at'=>now()->toIso8601String(),'target'=>$this->target,'selected'=>[],'finished'=>false];$this->save();
            }elseif($this->state['target']!==$this->target){$this->error('Er staat nog een ronde open met --per-category='.$this->state['target'].'. Rond die eerst af.');return self::FAILURE;}
            if($this->state['hour']!==$hour)$this->line('Een eerdere ronde is nog niet compleet; eerst die ronde afmaken.');
            $this->info('Doel: '.$this->target.' nieuwe artikelen met beeld per categorie, '.($this->target*6).' in totaal. Bestaande artikelen blijven behouden.');
            $cursors=[];$exhausted=[];$visits=[];$imageFailed=[];$pages=0;$assessed=false;
            while(true){
                $counts=$this->counts();$missing=array_keys(array_filter($counts,fn($count)=>$count<$this->target));
                if(!$missing){$this->state['finished']=true;$this->state['finished_at']=now()->toIso8601String();$this->save();$this->report();$this->info('Uurronde compleet: '.($this->target*6).' nieuwe artikelen met beeld.');return self::SUCCESS;}
                if(microtime(true)>=$this->deadline)return $this->paused('Uitvoeringstijd bereikt. De volgende uitvoering gaat verder met deze ronde.');
                $ready=Article::where('origin','news')->whereNull('hidden_at')->where('status','news_ready')->whereIn('category',$missing)->whereNotIn('id',$imageFailed)->latest('published_at')->latest('id')->first();
                if($ready){
                    if(app(NewsDuplicates::class)->hideIfDuplicate($ready)){$ready->save();continue;}
                    try {
                        $this->line('#'.$ready->id.' passende afbeelding maken/controleren');
                        $ready->update(['photo'=>array_merge($ready->photo??[],['requested_style'=>LichtpuntIllustrationPrompt::PHOTO_STYLE])]);
                        if(!$photos->attach($ready)){$imageFailed[]=$ready->id;continue;}
                        $ready->refresh();if(!$photos->current($ready)){$imageFailed[]=$ready->id;continue;}
                    }catch(ImageBudgetException $e){return $this->paused($e->getMessage());}
                    catch(\Throwable $e){Log::warning('Hourly news image deferred',['article_id'=>$ready->id,'exception'=>get_class($e)]);$imageFailed[]=$ready->id;$this->warn('#'.$ready->id.' beeld nog niet geschikt/beschikbaar; ander artikel zoeken.');continue;}
                    // Persist the selection BEFORE publishing: a crash between DB update and file save cannot double the quota.
                    $this->state['selected'][(string)$ready->id]=$ready->category;$this->save();
                    DB::transaction(function()use($ready){
                        $a=Article::whereKey($ready->id)->lockForUpdate()->first();
                        if($a&&$a->origin==='news'&&!$a->hidden_at&&$a->status==='news_ready'&&in_array($a->category,NewsCategoryBalance::CATEGORIES,true)&&$this->counts()[$a->category]<$this->target)$a->update(['status'=>'published','published_at'=>$a->published_at??now()]);
                    });
                    $this->line('#'.$ready->id.' '.$ready->fresh()->status);continue;
                }
                $candidate=Article::where('origin','news')->whereNull('hidden_at')->whereIn('status',['pending','error'])->where('attempts','<',3)
                    ->where(fn($q)=>$q->whereIn('category',$missing)->orWhereNull('category'))->latest('published_at')->latest('id')->first();
                if($candidate){
                    if($assessed)Sleep::for(max(0,(int)config('news_hourly.assessment_delay',8)))->seconds();$assessed=true;
                    $this->line('#'.$candidate->id.' positief nieuws beoordelen');$this->assess($importer,$candidate);
                    $this->line('#'.$candidate->id.' '.$candidate->fresh()->status);continue;
                }
                $sources=array_values(array_diff($missing,$exhausted));
                if(!$sources)return $this->paused('De beschikbare bronpaginas bevatten nu onvoldoende geschikte artikelen voor alle categorieen.');
                if((int)config('news_hourly.max_pages',0)>0&&$pages>=(int)config('news_hourly.max_pages'))return $this->paused('Bronpaginalimiet voor deze uitvoering bereikt. De ronde is nog niet compleet.');
                usort($sources,fn($a,$b)=>(($visits[$a]??0)<=>($visits[$b]??0))?:($counts[$a]<=>$counts[$b]));$cat=$sources[0];
                $page=$importer->fetchPage($cursors[$cat]??null,$cat);$pages++;$visits[$cat]=($visits[$cat]??0)+1;
                if(empty($page['next'])||$page['next']===($cursors[$cat]??null))$exhausted[]=$cat;
                $cursors[$cat]=$page['next']??null;$this->line(StorySeo::CATEGORIES[$cat][0].': '.count($page['created']).' nieuwe kandidaten opgehaald.');
            }
        }catch(\Throwable $e){
            if($e->getMessage()==='De uitvoeringstijd van deze ronde is voorbij.')return $this->paused('Uitvoeringstijd bereikt. De volgende uitvoering gaat verder met deze ronde.');
            Log::warning('Hourly news paused',['exception'=>get_class($e)]);
            $message=preg_match('/^(Gemini HTTP \d{3}\.|NewsData HTTP \d{3}\.)$/',$e->getMessage())?$e->getMessage():'Controleer nieuws-/AI-verbinding, configuratie en het Laravel-logboek.';
            if($this->state)return $this->paused('Uurronde onderbroken. '.$message,self::FAILURE);
            $this->error('Voortgang kon niet veilig worden gelezen. Geen artikelen gepubliceerd. '.$message);return self::FAILURE;
        }finally{$lock->release();}
    }
}
