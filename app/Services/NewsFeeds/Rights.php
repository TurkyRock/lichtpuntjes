<?php
namespace App\Services\NewsFeeds;

use App\Models\Article;

class Rights
{
    public function attribution(string $url,?string $author=null):?array {
        foreach(config('news-feeds.sources',[]) as $key=>$source){
            if(!app(Pool::class)->approved($source)||!app(FeedReader::class)->allowed($url,$source))continue;
            if(($source['author_required']??false)&&!$author)return null;
            return ['source_key'=>$key,'author'=>$author,'license'=>$source['license'],'license_url'=>$source['license_url'],'credit'=>$source['credit'],
                'regions'=>$source['regions']??[],'places'=>$source['places']??[],'reviewed'=>$source['reviewed']];
        }
        return null;
    }
    public function permitted(Article $a):bool {
        if(!config('news-feeds.strict_publisher_rights',true)||$a->origin!=='news')return true;
        $at=$a->source_attribution??[];
        // Existing aggregator summaries use their own terms/factual-summary route; no claim of a publisher licence.
        if(!$at)return true;
        $source=config('news-feeds.sources.'.($at['source_key']??''));
        return $source && app(Pool::class)->approved($source) && app(FeedReader::class)->allowed($a->source_url,$source)
            && (!($source['author_required']??false)||!empty($at['author']));
    }
}
