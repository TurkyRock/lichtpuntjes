<?php
namespace App\Console\Commands;

use App\Services\NewsFeeds\{Pool,Regions};
use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache,DB};

class NewsSources extends Command
{
    protected $signature='news:sources {--collect : Controleer toegelaten feeds} {--tick : Hervat regionale verwerking} {--status : Toon bronnen en alle provincies}';
    protected $description='Vul en bewaak de toegelaten internationale en regionale nieuwsvoorraad';
    public function handle(Pool $pool):int {
        if($this->option('status') || (!$this->option('collect')&&!$this->option('tick'))){
            $checks=DB::table('news_feed_checks')->get()->keyBy('source_key');
            $this->table(['Bron','Rechten','Laatste controle','Status'],collect(config('news-feeds.sources'))->map(function($s,$key)use($checks,$pool){$c=$checks[$key]??null;return [$s['name'],$pool->approved($s)?'gecontroleerd':'verlopen / uit', $c?->checked_at??'nog niet',$c?->message??'wacht op eerste broncontrole'];})->all());
            $this->table(['Provincie','Gepubliceerd','Wacht op verwerking'],collect(Regions::ALL)->map(fn($name,$region)=>[$name,Article::visible()->whereIn('id',DB::table('article_regions')->where('region',$region)->select('article_id'))->count(),DB::table('news_feed_entries')->where('status','queued')->whereJsonContains('regions',$region)->count()])->all());
            $this->line($pool->paused()?'Aanvulling gepauzeerd: wachtrijdruk.':'Aanvulling binnen wachtrijlimiet.');
            $this->line('Geen passend aanbod is geen storing. Alleen bronnen met toegelaten hergebruik doen mee.');return self::SUCCESS;
        }
        if(!config('news-feeds.enabled')||!\App\Support\NewsImportWindow::isOpen())return self::SUCCESS;
        if(!app(\App\Services\Maintenance\StorageUsage::class)->importsAllowed()){$this->warn('Aanvulling gepauzeerd: opslaglimiet.');return self::SUCCESS;}
        $lock=Cache::lock('lichtpunt-feed-'.($this->option('collect')?'collect':'tick'),180);if(!$lock->get())return self::SUCCESS;
        try{
            if($this->option('collect')){$pool->collect();$this->info('Bronnen gecontroleerd; zie news:sources --status.');}
            if($this->option('tick')){
                // Index an existing chunk as metadata only, without rewriting old stories.
                $cursor=(int)Cache::get('lichtpunt-region-index',0);
                $rows=Article::where('id','>',$cursor)->orderBy('id')->limit(100)->get();
                foreach($rows as $a){app(Regions::class)->sync($a);$cursor=$a->id;}
                Cache::forever('lichtpunt-region-index',$rows->isEmpty()?0:$cursor);
                $pool->processRegional();
            }
        }finally{$lock->release();}
        return self::SUCCESS;
    }
}
