<?php
namespace App\Http\Controllers;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ArticleAdminController extends Controller {
 public const STATUSES=['published'=>'Geplaatst','pending'=>'Wachtend','draft'=>'Concept','rejected'=>'Afgewezen','insufficient'=>'Onvoldoende informatie','error'=>'Fout','replacement'=>'Nieuwe batch','news_ready'=>'Goedgekeurd, afbeelding volgt','community_pending'=>'Eigen verhaal in behandeling'];
 private function owner(Request $request):void {abort_unless($request->user()?->is_admin,403);}
 public function index(Request $request){
  $this->owner($request);
  $input=$request->validate(['q'=>'nullable|string|max:200','status'=>'sometimes|in:published,hidden','origin'=>'sometimes|in:all,news,community','page'=>'sometimes|integer|min:1|max:1000000']);
  $status=$input['status']??'published';$origin=$input['origin']??'all';$search=trim($input['q']??'');$query=Article::query()->with('author');
  if($status==='hidden')$query->whereNotNull('hidden_at');elseif($status!=='all')$query->visible();
  if($origin!=='all')$query->where('origin',$origin);
  if($search!=='')$query->where(function($q)use($search){$term='%'.$search.'%';$q->where('nl->title','like',$term)->orWhere('en->title','like',$term)->orWhere('source_title','like',$term)->orWhere('source_name','like',$term);if(ctype_digit($search))$q->orWhere('id',(int)$search);});
  $articles=$query->orderByDesc('id')->paginate(30)->withQueryString();
  $daily=app(\App\Services\DailySpotlight::class)->choose();$dailyArticle=$daily?Article::visible()->find($daily->article_id):null;
  return view('community.articles-admin',compact('articles','status','origin','search','daily','dailyArticle'));
 }
 public function visibility(Request $request,Article $article){
  $this->owner($request);$hidden=(bool)$request->validate(['hidden'=>'required|boolean'])['hidden'];
  DB::transaction(function()use($article,$hidden,$request){
   $a=Article::whereKey($article->id)->lockForUpdate()->firstOrFail();
   abort_unless($a->status==='published',422,'Alleen gepubliceerde artikelen kunnen hier worden verborgen of teruggeplaatst. Gebruik voor andere berichten de bestaande moderatie.');
   if(!$hidden&&$a->origin==='community')abort_unless($a->author&&!$a->author->is_suspended,422,'Dit gebruikersaccount is gepauzeerd. Het artikel kan niet worden teruggeplaatst.');
   if($hidden&&!$a->hidden_at)$a->update(['hidden_at'=>now(),'hidden_by'=>$request->user()->id]);
   elseif(!$hidden&&$a->hidden_at)$a->update(['hidden_at'=>null,'hidden_by'=>null]);
  });
  return back()->with('status',$hidden?'Het artikel is verwijderd van de site. Je kunt het bij Verwijderd terugplaatsen.':'Het artikel is teruggeplaatst.');
 }
 public function daily(Request $request,?Article $article=null){
  $this->owner($request);$service=app(\App\Services\DailySpotlight::class);$before=$service->choose();$result=$service->choose(true,$article);
  return back()->with('status',$result&&$result->article_id!==($before->article_id??null)?'Het lichtpuntje van de dag is aangepast.':($article&&$result?'Dit bericht staat uitgelicht.':'Er is nu geen ander geschikt dagverhaal. De huidige keuze blijft staan.'));
 }

}
