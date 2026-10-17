<?php
namespace App\Console\Commands;

use App\Models\Article;
use App\Services\{NewsImporter,ArticlePhotoFinder,NewsServiceException,ImageBudgetException};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache,DB};
use Illuminate\Support\Sleep;

class RunNewsCycle extends Command
{
    protected $signature='news:cycle';
    protected $description='Haal maximaal 30 kandidaten op en publiceer maximaal 6 geïllustreerde lichtpuntjes';

    public function handle(NewsImporter $importer,ArticlePhotoFinder $photos): int
    {
        $lock=Cache::lock('lichtpunt-import',10800);
        if(!$lock->get()){$this->warn('Er loopt al een import.');return self::SUCCESS;}
        try {
            $this->info($importer->fetch((int)config('news_cycle.pages',3)).' nieuwe kandidaten opgeslagen.');
            $pending=app(\App\Services\NewsCategoryBalance::class)->prioritise(Article::where('origin','news')->whereNull('hidden_at')->whereIn('status',['pending','error'])->where('attempts','<',3))
                ->oldest('id')->limit((int)config('news_cycle.assess_limit',30))->get();
            foreach($pending as $i=>$article) {
                if($i>0)Sleep::for(8)->seconds();
                try {$importer->assess($article,true,'news_ready');}
                catch(\Throwable $e){$article->update(['status'=>'error','reason'=>'Keuring tijdelijk mislukt. Controleer API-model, verbinding en quota.']);$this->warn('Keuring gestopt wegens servicefout. Opgeslagen berichten blijven bewaard.');break;}
            }
            $published=0;$limit=(int)config('news_cycle.publish_limit',6);
            $balance=app(\App\Services\NewsCategoryBalance::class);
            while($published<$limit) {
                $counts=$balance->counts();$minimum=min($counts);
                $categories=array_keys(array_filter($counts,fn($count)=>$count===$minimum));
                $a=Article::where('origin','news')->whereNull('hidden_at')->where('status','news_ready')->whereIn('category',$categories)->orderByDesc('published_at')->orderByDesc('id')->first();
                if(!$a)break;
                try {
                    if(!$photos->attach($a))break;
                    $a->refresh();if(!$photos->current($a))throw new NewsServiceException('Illustratie ontbreekt.');
                    DB::transaction(function()use($a){$locked=Article::whereKey($a->id)->lockForUpdate()->firstOrFail();if(!$locked->hidden_at&&$locked->status==='news_ready')$locked->update(['status'=>'published','published_at'=>$locked->published_at??now()]);});
                    $published++;$this->info('#'.$a->id.' gepubliceerd.');
                } catch(ImageBudgetException $e) {$this->warn($e->getMessage());break;}
                catch(\Throwable $e){$this->warn('Illustraties tijdelijk niet beschikbaar. Berichten blijven klaarstaan voor de volgende ronde.');break;}
            }
            $this->info($published.' van maximaal '.$limit.' nieuwe berichten gepubliceerd.');
            return self::SUCCESS;
        } catch(\Throwable $e){$this->error('Nieuwscyclus mislukt. Controleer verbinding, API-sleutels en database. Geen oude berichten verwijderd.');return self::FAILURE;}
        finally {$lock->release();}
    }
}
