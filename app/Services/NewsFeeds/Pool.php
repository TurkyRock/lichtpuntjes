<?php
namespace App\Services\NewsFeeds;

use App\Models\Article;
use Illuminate\Support\Facades\{Cache,DB};

class Pool
{
    public function approved(array $source):bool {
        try{return !empty($source['commercial_reuse']) && !empty($source['adaptations']) && !empty($source['policy']) && !empty($source['license']) && \Carbon\CarbonImmutable::parse($source['reviewed'])->addDays(90)->isFuture();}
        catch(\Throwable $e){return false;}
    }
    public function collect():void {
        foreach(config('news-feeds.sources',[]) as $key=>$source){
            if(!\App\Support\NewsImportWindow::isOpen())return;
            $last=DB::table('news_feed_checks')->where('source_key',$key)->first();
            if($last?->checked_at && \Carbon\CarbonImmutable::parse($last->checked_at)->gt(now()->subMinutes(55)))continue;
            $check=['checked_at'=>now(),'new_entries'=>0];
            try{
                if(!$this->approved($source))throw new \RuntimeException('Rechtencontrole verlopen; bron gepauzeerd.');
                $reader=app(FeedReader::class);$entries=$reader->parse($reader->download($source['feed'],$source),$source);
                foreach($entries as $entry){
                    $regions=$source['regions']??[];
                    // A regional pool is based on explicit province names in source text, not the publisher's office.
                    if($key==='rijksoverheid')foreach(Regions::ALL as $r=>$name)if(preg_match('/(?<![\pL])'.preg_quote($name,'/').'(?![\pL])/iu',$entry['title'].' '.$entry['body']))$regions[]=$r;
                    $category=$this->category($entry['title'].' '.$entry['body']);
                    $check['new_entries']+=DB::table('news_feed_entries')->insertOrIgnore($entry+[
                        'source_key'=>$key,'url_hash'=>hash('sha256',$entry['url']),'category'=>$category,
                        'regions'=>json_encode(array_unique($regions)),'places'=>json_encode($source['places']??[]),
                        'created_at'=>now(),'updated_at'=>now(),
                    ]);
                }
                $check+=['succeeded_at'=>now(),'status'=>'ok','message'=>count($entries).' toegelaten kandidaten in feed.'];
            }catch(\Throwable $e){
                $message=$e instanceof \RuntimeException && preg_match('/^(Bron HTTP \d{3}\.|Geen geldige RSS|Bronadres|Feed te groot|Onveilige XML|Rechtencontrole)/',$e->getMessage())?$e->getMessage():'Bron tijdelijk niet bereikbaar; volgende ronde opnieuw.';
                $check+=['status'=>'error','message'=>mb_substr($message,0,250)];
            }
            DB::table('news_feed_checks')->updateOrInsert(['source_key'=>$key],$check);
        }
        DB::table('news_feed_entries')->where('published_at','<',now()->subDays(30))->where('status','queued')->delete();
    }
    private function category(string $text):string {
        if(preg_match('/wildlife|biodiversity|animals|birds|forest|natuur|dieren|bloemen|park\b|bomen|biodiversiteit|ecosystem|conservation/iu',$text))return 'nature';
        if(preg_match('/renewable|solar|recycl|hergebruik|duurzaam|zonne.energie|windenergie/iu',$text))return 'energy';
        if(preg_match('/festival|museum|music|muziek|kunst|theater|culture/iu',$text))return 'media';
        if(preg_match('/sport|football|voetbal|athlet/iu',$text))return 'sport';
        if(preg_match('/vrijwillig|buurt|community|kindness|volunteer/iu',$text))return 'people';
        return 'science';
    }
    public function paused():bool {
        $s=app(\App\Services\PipelineStatus::class)->snapshot();
        return $s['images']['queued']+$s['images']['processing']+$s['images']['generated'] >= config('news-feeds.queue_pause_at',10)
            || $s['text']['queued']+$s['text']['processing']>=config('news-feeds.queue_pause_at',10);
    }
    public function select(bool $regional,?string $category=null):?Article {
        if($this->paused() || !\App\Support\NewsImportWindow::isOpen())return null;
        return Cache::lock('lichtpunt-feed-selection',15)->block(2,function()use($regional,$category){
            $now=now('UTC');$start=$now->copy()->startOfHour();
            $quota=(int)config($regional?'news-feeds.regional_per_hour':'news-feeds.international_per_hour',2);
            $used=DB::table('news_feed_entries')->where('selected_at','>=',$start)->whereJsonLength('regions',$regional?'>':'=',0)->count();
            if($used>=$quota)return null;
            $rows=DB::table('news_feed_entries')->where('status','queued')->where('published_at','>=',now()->subDays(30))
                ->whereJsonLength('regions',$regional?'>':'=',0)->when($category,fn($q)=>$q->where('category',$category))->latest('published_at')->limit(100)->get();
            if($regional){
                $counts=DB::table('article_regions')->join('articles','articles.id','=','article_regions.article_id')->where('articles.status','published')->whereNull('articles.hidden_at')->where('articles.published_at','>=',now()->subDays(7))->groupBy('region')->selectRaw('region,count(*) as total')->pluck('total','region');
                $rows=$rows->sortBy(fn($r)=>min(array_map(fn($region)=>(int)($counts[$region]??0),json_decode($r->regions,true))));
            }else{
                // Rotate publishers to stop a busy source swallowing all supplement slots.
                $last=DB::table('news_feed_entries')->whereNotNull('selected_at')->latest('selected_at')->value('source_key');
                $rows=$rows->sortBy(fn($r)=>$r->source_key===$last?1:0);
            }
            foreach($rows as $row){
                $source=config('news-feeds.sources.'.$row->source_key);
                if(!$source || !$this->approved($source))continue;
                $values=['source_title'=>$row->title,'source_description'=>$row->body,'source_name'=>$source['name'],'source_url'=>$row->url,'category'=>$row->category,'published_at'=>$row->published_at,
                    'status'=>$regional?'feed_pending':'pending','source_language'=>$source['language'],
                    'source_attribution'=>['source_key'=>$row->source_key,'author'=>$row->author,'license'=>$source['license'],'license_url'=>$source['license_url'],'credit'=>$source['credit'],
                        'regions'=>json_decode($row->regions,true),'places'=>json_decode($row->places,true),'reviewed'=>$source['reviewed']]];
                $article=Cache::lock('lichtpunt-news-title-import',30)->block(2,function()use($row,$values){
                    return DB::transaction(function()use($row,$values){
                        if(app(\App\Services\NewsDuplicates::class)->find(new Article($values))){DB::table('news_feed_entries')->where('id',$row->id)->update(['status'=>'duplicate','updated_at'=>now()]);return null;}
                        $a=Article::firstOrCreate(['fingerprint'=>$row->url_hash],$values);
                        DB::table('news_feed_entries')->where('id',$row->id)->update(['status'=>$a->wasRecentlyCreated?'selected':'duplicate','article_id'=>$a->id,'selected_at'=>$a->wasRecentlyCreated?now():null,'updated_at'=>now()]);
                        return $a->wasRecentlyCreated?$a:null;
                    });
                });
                if($article)return $article;
            }
            return null;
        });
    }
    public function processRegional():void {
        if(!\App\Support\NewsImportWindow::isOpen())return;
        $lock=Cache::lock('lichtpunt-import',120);if(!$lock->get())return;
        try{
            $entry=DB::table('news_feed_entries')->join('articles','articles.id','=','news_feed_entries.article_id')->where('news_feed_entries.status','selected')->whereJsonLength('news_feed_entries.regions','>',0)
                ->whereIn('articles.status',['feed_pending','feed_ready'])->orderBy('news_feed_entries.selected_at')->select('articles.id')->first();
            $a=$entry?Article::find($entry->id):$this->select(true);
            if(!$a)return;
            $source=config('news-feeds.sources.'.($a->source_attribution['source_key']??''));
            if(!$source || !$this->approved($source)){$a->update(['status'=>'draft','reason'=>'Bronrechten vragen een nieuwe controle.']);return;}
            if($a->status==='feed_pending'){
                try{app(\App\Services\NewsImporter::class)->assess($a,true,'news_ready');}
                catch(\App\Services\AiWaitException $e){return;}
                catch(\Throwable $e){$a->refresh();$a->update(['attempts'=>max(1,$a->attempts),'reason'=>'Aanvullende bronverwerking tijdelijk mislukt.']);if($a->attempts>=3)$a->update(['status'=>'draft']);return;}
                $a->refresh();if($a->status==='news_ready')$a->update(['status'=>'feed_ready']);else return;
            }
            try{
                $photos=app(\App\Services\ArticlePhotoFinder::class);
                $a->update(['photo'=>array_merge($a->photo??[],['requested_style'=>\App\Services\LichtpuntIllustrationPrompt::PHOTO_STYLE])]);
                $photos->attach($a);$a->refresh();
                if($photos->current($a))$a->update(['status'=>'published','published_at'=>now()]);
            }catch(\App\Services\AiWaitException|\App\Services\ImagePendingException $e){return;}
            catch(\Throwable $e){$a->update(['status'=>'draft','reason'=>'Beeldcontrole vraagt aandacht; artikel niet gepubliceerd.']);}
        }finally{$lock->release();}
    }
}
