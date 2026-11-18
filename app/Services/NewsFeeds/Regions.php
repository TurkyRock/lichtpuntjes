<?php
namespace App\Services\NewsFeeds;

use App\Models\Article;
use Illuminate\Support\Facades\DB;

class Regions
{
    public const ALL=['groningen'=>'Groningen','friesland'=>'Friesland','drenthe'=>'Drenthe','overijssel'=>'Overijssel','flevoland'=>'Flevoland','gelderland'=>'Gelderland','utrecht'=>'Utrecht','noord-holland'=>'Noord-Holland','zuid-holland'=>'Zuid-Holland','zeeland'=>'Zeeland','noord-brabant'=>'Noord-Brabant','limburg'=>'Limburg'];
    public static function key(string $name):string {
        $name=\App\Support\StoryLocation::normalize($name);
        return str_replace(['noord holland','zuid holland','noord brabant','fryslân','fryslan'],['noord-holland','zuid-holland','noord-brabant','friesland','friesland'],$name);
    }
    public static function fromPlaces(array $places):array {
        $mapping=config('news-regions.places',[]);$regions=[];
        foreach($places as $place){
            if(!is_string($place))continue;
            $key=self::key($place);
            if(isset(self::ALL[$key]))$regions[]=$key;
            elseif(isset($mapping[$key]) && count($mapping[$key])===1)$regions[]=$mapping[$key][0];
        }
        return array_values(array_unique($regions));
    }
    public static function selected(?\App\Models\User $user):array {
        $p=$user?->news_preferences??[];
        return self::fromPlaces([$p['place']??'', $p['region']??'']);
    }
    public function sync(Article $article):void {
        $regions=($article->story_country==='NL' || $article->origin==='community' || !empty($article->source_attribution['regions']))
            ?self::fromPlaces($article->story_places??[]):[];
        foreach($article->source_attribution['regions']??[] as $r)if(isset(self::ALL[$r]))$regions[]=$r;
        DB::transaction(function()use($article,$regions){
            DB::table('article_regions')->where('article_id',$article->id)->delete();
            foreach(array_unique($regions) as $r)DB::table('article_regions')->insertOrIgnore(['article_id'=>$article->id,'region'=>$r]);
        });
    }
}
