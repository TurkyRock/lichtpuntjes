<?php
namespace App\Http\Controllers;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsEngagementController extends Controller {
    private function visitor(Request $request): string {
        $id=$request->cookie('lichtpunt_visitor');
        if (!is_string($id)||!Str::isUuid($id)) {
            $id=(string)Str::uuid();
            // Willekeurige browsercode, geen IP-adres of gebruikersprofiel.
            Cookie::queue(cookie('lichtpunt_visitor',$id,\App\Support\CookieChoice::MINUTES,'/',null,$request->isSecure(),true,false,'lax'));
        }
        return hash_hmac('sha256',$id,(string)config('app.key'));
    }
    public function index(Request $request) {
        $input=$request->validate([
            'page'=>'sometimes|integer|min:1|max:1000000',
            'category'=>'sometimes|in:all,community,media,sport,energy,nature,science,people',
            'q'=>'sometimes|nullable|string|max:200','lang'=>'sometimes|in:nl,en',
            'view'=>'sometimes|in:all,for-you,nearby','sort'=>'sometimes|in:newest,reads,likes','archive'=>'sometimes|boolean',
        ]);
        $language=$input['lang']??'nl';
        $query=Article::visible();
        if($request->boolean('archive'))$query->where('origin','news')->where('published_at','<',now()->subDays(config('retention.archive_days',90)));
        else $query->where(fn($q)=>$q->where('origin','community')->orWhere('published_at','>=',now()->subDays(config('retention.archive_days',90))));
        $category=$input['category']??'all';
        if ($category==='community') $query->where('origin','community');
        elseif ($category!=='all') $query->where('origin','news')->where('category',$category);
        $search=trim($input['q']??'');
        if ($search!=='') $query->where(function($q)use($language,$search){
            $term='%'.$search.'%';
            $q->where($language.'->title','like',$term)->orWhere($language.'->teaser','like',$term)
                ->orWhere($language.'->body','like',$term)->orWhere('source_name','like',$term);
        });
        $preferences=$request->user()?->news_preferences??[];
        $nearby=($input['view']??'')==='nearby';
        if($nearby){
            $places=\App\Support\StoryLocation::places($preferences['place']??null,$preferences['region']??null);
            if(!app(\App\Services\NewsPreferences::class)->enabled($request->user()) || !$places)$query->whereRaw('1 = 0');
            else $query->where(function($q)use($places,$request){
                foreach($places as $place)$q->orWhereJsonContains('story_places',$place);
                $regions=\App\Services\NewsFeeds\Regions::selected($request->user());
                if($regions)$q->orWhereIn('articles.id',DB::table('article_regions')->whereIn('region',$regions)->select('article_id'));
            });
        }
        $sort=$input['sort']??'newest';
        // Round robin: one story per available category before its next story.
        // Community origin groups old and new user stories together without a data rewrite.
        $order=match($sort) {
            'reads'=>'articles.views_count DESC, articles.published_at DESC, articles.id DESC',
            'likes'=>'articles.likes_count DESC, articles.published_at DESC, articles.id DESC',
            default=>'articles.published_at DESC, articles.id DESC',
        };
        $personalized = !($request->boolean('archive')) && ($input['view']??'for-you') === 'for-you'
            && app(\App\Services\NewsPreferences::class)->enabled($request->user());
        if ($personalized) {
            [$score,$bindings] = app(\App\Services\NewsPreferences::class)->relevance($request->user());
            $scored = (clone $query)->select('articles.id')->selectRaw("($score) AS personal_score",$bindings);
            $query->joinSub($scored,'personal',fn($join)=>$join->on('articles.id','=','personal.id'));
        }
        if ($category==='all') {
            $categoryOrder = $personalized ? 'personal.personal_score DESC, '.$order : $order;
            $ranked=(clone $query)->select('articles.id')->selectRaw(
                "ROW_NUMBER() OVER (PARTITION BY CASE WHEN articles.origin = 'community' THEN 'community' ELSE articles.category END ORDER BY $categoryOrder) AS category_position"
            );
            $query->joinSub($ranked,'balanced',fn($join)=>$join->on('articles.id','=','balanced.id'));
            // At most two stories per category before the next round: personal, but varied.
            if ($personalized) $query->orderByRaw('FLOOR((balanced.category_position - 1) / 2.0)');
            else $query->orderBy('balanced.category_position');
        }
        if ($personalized) $query->orderByDesc('personal.personal_score');
        $query->orderByRaw($order);
        $items=$query->paginate(12,['articles.*'],'page',(int)($input['page']??1));
        if ($items->currentPage()>$items->lastPage()) $items=$query->paginate(12,['articles.*'],'page',$items->lastPage());
        $liked=$request->user()?DB::table('user_article_likes')->where('user_id',$request->user()->id)->whereIn('article_id',$items->getCollection()->pluck('id'))->pluck('article_id')->all():[];
        return response()->json([
            'personalized'=>$personalized, 'nearby'=>$nearby,
            'articles'=>$items->getCollection()->map(fn($a)=>[
                'id'=>(string)$a->id,'story_urls'=>\App\Support\StorySeo::urls($a),'cat'=>$a->origin==='community'?'community':$a->category,'date'=>$a->published_at?->format('Y-m-d'),
                'attribution'=>$a->source_attribution,'places'=>$a->story_places??[], 'local'=>($personalized || $nearby) && (\App\Support\StoryLocation::matches($a->story_places??[],$request->user()->news_preferences??[]) || (bool)array_intersect(\App\Services\NewsFeeds\Regions::selected($request->user()),\App\Services\NewsFeeds\Regions::fromPlaces($a->story_places??[]))),
                'origin'=>$a->origin,'source'=>$a->source_name,'url'=>$a->source_url,'nl'=>$a->nl,'en'=>$a->en,'photo'=>$a->photo,
                'views_count'=>(int)$a->views_count,'likes_count'=>(int)$a->likes_count,
                'liked'=>in_array($a->id,$liked),
            ])->values(),
            'pagination'=>['current_page'=>$items->currentPage(),'last_page'=>$items->lastPage(),
                'per_page'=>$items->perPage(),'total'=>$items->total(),'from'=>$items->firstItem(),'to'=>$items->lastItem()],
        ])->header('Cache-Control','private, no-store');
    }
    public function read(Request $request, Article $article) {
        abort_unless(Article::visible()->whereKey($article->id)->exists(),404);
        app(\App\Services\NewsReadingInterests::class)->record($request->user(),$article,'read');
        if($request->user()&&!$request->user()->is_suspended&&$request->user()->community_accepted_at)app(\App\Services\Lightpoints::class)->award($request->user(),'read:'.now('Europe/Amsterdam')->toDateString().':'.$article->id,'read',1,'Je dagelijkse dosis goed nieuws',5);
        if(!\App\Support\CookieChoice::allowed($request))return response()->json(['views_count'=>(int)$article->views_count,'counted'=>false])->header('Cache-Control','private, no-store');
        $visitor=$this->visitor($request);
        $stats=DB::transaction(function()use($article,$visitor){
            $a=Article::whereKey($article->id)->visible()->lockForUpdate()->firstOrFail();
            $added=DB::table('article_reads')->insertOrIgnore([
                'article_id'=>$a->id,'visitor_hash'=>$visitor,'read_date'=>now('Europe/Amsterdam')->toDateString(),
            ]);
            if ($added) $a->increment('views_count');
            return ['views_count'=>(int)$a->views_count];
        });
        return response()->json($stats)->header('Cache-Control','private, no-store');
    }
    public function story(Request $request,Article $article){
        abort_unless(Article::visible()->whereKey($article->id)->exists(),404);
        return response()->json(['id'=>(string)$article->id,'story_urls'=>\App\Support\StorySeo::urls($article),'cat'=>$article->origin==='community'?'community':$article->category,'date'=>$article->published_at?->format('Y-m-d'),'attribution'=>$article->source_attribution,'places'=>$article->story_places??[],'origin'=>$article->origin,'source'=>$article->source_name,'url'=>$article->source_url,'nl'=>$article->nl,'en'=>$article->en,'photo'=>$article->photo,'views_count'=>(int)$article->views_count,'likes_count'=>(int)$article->likes_count,'liked'=>$request->user()?DB::table('user_article_likes')->where('user_id',$request->user()->id)->where('article_id',$article->id)->exists():false])->header('Cache-Control','private, no-store');
    }
    public function like(Request $request, Article $article) {
        abort_unless(Article::visible()->whereKey($article->id)->exists(),404);
        $desired=(bool)$request->validate(['liked'=>'required|boolean'])['liked'];
        abort_unless($request->user(),401);abort_if($request->user()->is_suspended,403);abort_unless($request->user()->community_accepted_at,403,'Bevestig de huisregels in je profiel.');
        $userId=$request->user()->id;
        $stats=DB::transaction(function()use($article,$userId,$desired){
            $user=\App\Models\User::whereKey($userId)->lockForUpdate()->firstOrFail();abort_if($user->is_suspended,403);
            $a=Article::whereKey($article->id)->visible()->lockForUpdate()->firstOrFail();
            $awarded=false;
            $likes=DB::table('user_article_likes')->where('article_id',$a->id)->where('user_id',$userId);
            if ($desired) {
                $added=DB::table('user_article_likes')->insertOrIgnore([
                    'article_id'=>$a->id,'user_id'=>$userId,'created_at'=>now(),
                ]);
                if ($added) $a->increment('likes_count');
                if((int)$a->author_id!==$userId)$awarded=app(\App\Services\Lightpoints::class)->award($user,'heart:article:'.now('Europe/Amsterdam')->toDateString().':'.$a->id,'heart',1,'Je gaf wat liefde met een hartje');
            } else {
                $deleted=$likes->delete();
                if ($deleted&&$a->likes_count>0) $a->decrement('likes_count');
            }
            app(\App\Services\NewsReadingInterests::class)->record($user,$a,'like',$desired);
            return ['likes_count'=>(int)$a->likes_count,'liked'=>$desired,'awarded'=>$awarded,'points_awarded'=>$awarded?1:0];
        });
        return response()->json($stats)->header('Cache-Control','private, no-store');
    }
}
