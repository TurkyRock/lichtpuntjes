<?php
namespace App\Http\Controllers;
use App\Services\NewsFeeds\{Pool,Regions};
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class NewsSourceController extends Controller {
    public function index(Request $request){
        abort_unless($request->user()?->is_admin,403);
        $checks=DB::table('news_feed_checks')->get()->keyBy('source_key');$pool=app(Pool::class);
        $sources=collect(config('news-feeds.sources'))->map(fn($s,$key)=>$s+['key'=>$key,'approved'=>$pool->approved($s),'check'=>$checks[$key]??null]);
        $regions=collect(Regions::ALL)->map(fn($name,$key)=>['name'=>$name,
            'published'=>Article::visible()->whereIn('id',DB::table('article_regions')->where('region',$key)->select('article_id'))->count(),
            'queued'=>DB::table('news_feed_entries')->where('status','queued')->whereJsonContains('regions',$key)->count(),
            'processing'=>DB::table('news_feed_entries')->join('articles','articles.id','=','news_feed_entries.article_id')->whereJsonContains('news_feed_entries.regions',$key)->whereIn('articles.status',['feed_pending','feed_ready'])->count(),
            'attention'=>DB::table('news_feed_entries')->join('articles','articles.id','=','news_feed_entries.article_id')->whereJsonContains('news_feed_entries.regions',$key)->whereIn('articles.status',['draft','error'])->count(),
        ]);
        return view('community.news-sources',['sources'=>$sources,'regions'=>$regions,'paused'=>$pool->paused()]);
    }
}
