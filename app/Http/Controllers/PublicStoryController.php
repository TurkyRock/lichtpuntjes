<?php
namespace App\Http\Controllers;
use App\Models\Article;
use App\Support\StorySeo;
use Illuminate\Http\Request;
class PublicStoryController extends Controller {
 public function home(Request $request){
  $locale=(string)$request->route('locale','nl');$category=(string)$request->route('category','all');
  $request->validate(['page'=>'sometimes|integer|min:1|max:1000000','lang'=>'sometimes|in:nl,en','category'=>'sometimes|in:'.implode(',',array_keys(StorySeo::CATEGORIES))]);
  if(!$request->route('category')&&$request->query('category')&&$request->query('category')!=='all'){
   $language=$request->query('lang')==='en'?'en':$locale;
   $path=StorySeo::home($language,$request->query('category'),(int)$request->query('page',1));
   $extra=$request->except(['category','lang','page']);
   return redirect($path.($extra?(str_contains($path,'?')?'&':'?').http_build_query($extra):''),301);
  }
  if($request->query('story')!==null){$id=$request->query('story');abort_unless(is_string($id)&&ctype_digit($id),404);$a=Article::visible()->findOrFail($id);return redirect(StorySeo::path($a,$request->query('lang')==='en'?'en':$locale),301);}
  if($request->query('lang')==='en'&&$locale==='nl')return redirect(StorySeo::home('en',$category,(int)$request->query('page',1)),301);
  abort_unless(isset(StorySeo::CATEGORIES[$category]),404);
  app()->setLocale($locale);$request->session()->put('lichtpunt_locale',$locale);
  $request->query->set('lang',$locale);$request->query->set('category',$category);
  $feed=app(NewsEngagementController::class)->index($request)->getData(true);
  $feed['articles']=array_map(function($story){foreach(['nl','en'] as $lang){$story[$lang]['teaser']=(string)($story[$lang]['teaser']??'');}return $story;},$feed['articles']);
  $page=$feed['pagination']['current_page'];
  if((int)$request->query('page',1)>$page)return redirect(StorySeo::home($locale,$category,$page),301);
  $seo=StorySeo::meta($locale,$category,$page);
  if($category!=='all'&&$feed['pagination']['total']===0)$seo['robots']='noindex, follow';
  $highlights=[];foreach(app(\App\Services\DailySpotlight::class)->highlights() as $item){$story=app(NewsEngagementController::class)->story($request,$item['article'])->getData(true);$highlights[]=['kind'=>$item['kind'],'story'=>$story];}
  $momentHighlights=\App\Models\PositiveMoment::publicVisible()->with('user.profilePhoto')->withCount(['hearts'=>fn($q)=>$q->where('is_suspended',false)])->latest('id')->limit(30)->get()->unique('user_id')->take(4)->values();
  $likedMomentIds=$request->user()?\Illuminate\Support\Facades\DB::table('moment_hearts')->where('user_id',$request->user()->id)->whereIn('moment_id',$momentHighlights->pluck('id'))->pluck('moment_id')->all():[];
  $showMomentPrompt=now('Europe/Amsterdam')->hour>=19&&(!$request->user()||!\App\Models\PositiveMoment::where('user_id',$request->user()->id)->whereDate('moment_date',now('Europe/Amsterdam')->toDateString())->exists());
  $response=response()->view('lichtpunt',compact('seo','feed','category','locale','highlights','momentHighlights','showMomentPrompt','likedMomentIds'));
  if(isset($seo['robots']))$response->headers->set('X-Robots-Tag',$seo['robots']);
  return $response;
 }
 public function article(Request $request){
  $locale=(string)$request->route('locale','nl');$id=(int)$request->route('id');
  $article=Article::visible()->findOrFail($id);
  abort_unless(StorySeo::readable($article),404);
  $path=StorySeo::path($article,$locale);
  if($request->getPathInfo()!==$path)return redirect($path,301);
  app()->setLocale($locale);$request->session()->put('lichtpunt_locale',$locale);
  $category=$article->origin==='community'?'community':$article->category;$seo=StorySeo::meta($locale,$category,1,$article);
  $story=app(NewsEngagementController::class)->story($request,$article)->getData(true);
  $story['content_updated_at']=$article->content_updated_at?->toDateString();
  $feed=['articles'=>[],'pagination'=>['current_page'=>1,'last_page'=>1,'per_page'=>9,'total'=>0,'from'=>null,'to'=>null]];
  $relatedArticles=Article::visible()->whereKeyNot($article->id)->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END',[$article->category])->latest('published_at')->latest('id')->limit(9)->get()->filter(fn($a)=>StorySeo::readable($a))->take(3);
  $seoRelated=$relatedArticles->map(fn($a)=>app(NewsEngagementController::class)->story($request,$a)->getData(true))->values()->all();
  return view('lichtpunt',['seo'=>$seo,'feed'=>$feed,'seoArticle'=>$article,'seoStory'=>$story,'category'=>$category,'locale'=>$locale,'relatedArticles'=>$relatedArticles,'seoRelated'=>$seoRelated]);
 }
 public function sitemap(){
  return response()->stream(function(){
   echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
   $entry=function(array $paths,?string $date=null){foreach($paths as $path){echo '<url><loc>'.htmlspecialchars(StorySeo::absolute($path),ENT_XML1).'</loc>';if($date)echo '<lastmod>'.$date.'</lastmod>';foreach($paths as $lang=>$alternate)echo '<xhtml:link rel="alternate" hreflang="'.$lang.'" href="'.htmlspecialchars(StorySeo::absolute($alternate),ENT_XML1).'"/>';echo '</url>';}};
   foreach(array_keys(StorySeo::CATEGORIES) as $category){
    if($category!=='all'){
     $q=Article::visible();
     if($category==='community')$q->where('origin','community');
     else $q->where('origin','news')->where('category',$category)->where('published_at','>=',now()->subDays(config('retention.archive_days',90)));
     if(!$q->exists())continue;
    }
    $entry(['nl'=>StorySeo::home('nl',$category),'en'=>StorySeo::home('en',$category)]);
   }
   $entry(['nl'=>'/over-ons','en'=>'/en/about']);$entry(['nl'=>'/werkwijze','en'=>'/en/how-we-work']);
   if(Article::visible()->where('origin','news')->where('published_at','<',now()->subDays(config('retention.archive_days',90)))->exists())$entry(['nl'=>'/archief','en'=>'/archief?lang=en']);
   Article::visible()->orderBy('id')->chunkById(500,function($articles)use($entry){foreach($articles as $a){if(!StorySeo::readable($a))continue;$entry(StorySeo::urls($a),($a->content_updated_at??$a->published_at)?->toIso8601String());}});
   echo '</urlset>';
  },200,['Content-Type'=>'application/xml; charset=UTF-8','Cache-Control'=>'public, max-age=300']);
 }
 public function archive(Request $request){
  $request->validate(['page'=>'sometimes|integer|min:1|max:1000000','lang'=>'sometimes|in:nl,en']);
  $locale=$request->query('lang')==='en'?'en':'nl';app()->setLocale($locale);
  $articles=Article::visible()->where('origin','news')->where('published_at','<',now()->subDays(config('retention.archive_days',90)))->latest('published_at')->latest('id')->paginate(20);
  $page=$articles->currentPage();
  if($page>$articles->lastPage()){
   $params=array_filter(['lang'=>$locale==='en'?'en':null,'page'=>$articles->lastPage()>1?$articles->lastPage():null]);
   return redirect('/archief'.($params?'?'.http_build_query($params):''),301);
  }
  if($locale==='en')$articles->appends(['lang'=>'en']);
  $seo=StorySeo::document('/archief',$locale,$page);
  if(!$articles->total())$seo['robots']='noindex, follow';
  $response=response()->view('community.news-archive',compact('articles','seo','locale'));
  if(isset($seo['robots']))$response->headers->set('X-Robots-Tag',$seo['robots']);
  return $response;
 }
 public function information(Request $request){
  $locale=(string)$request->route('locale','nl');$page=(string)$request->route('page','about');
  app()->setLocale($locale);$seo=StorySeo::information($locale,$page);
  return view('seo.information',compact('seo','locale','page'));
 }
 public function robots(){return response("User-agent: *\nDisallow: /beheer\nDisallow: /profiel\nDisallow: /mijn-\nDisallow: /api/\nDisallow: /auth/\nDisallow: /news/\nSitemap: ".StorySeo::absolute('/sitemap.xml')."\n",200,['Content-Type'=>'text/plain; charset=UTF-8']);}
}
