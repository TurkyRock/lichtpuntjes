<?php
namespace App\Http\Controllers;
use App\Models\{Article,SocialPublication,SocialDelivery};
use App\Services\Social\{Planner,PostImage};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
class SocialPublishingController extends Controller {
 private function admin(Request $r):void {abort_unless($r->user()?->is_admin,403);}
 private function channel(Request $r):string {$v=$r->validate(['channel'=>'sometimes|in:'.implode(',',array_keys(config('social-publishing.channels')))]);return $v['channel']??'instagram';}
 public function index(Request $r,Planner $planner){
  $this->admin($r);$v=$r->validate(['date'=>'nullable|date_format:Y-m-d','kind'=>'sometimes|in:daily,reads,likes','history_date'=>'nullable|date_format:Y-m-d','history_channel'=>'nullable|in:'.implode(',',array_keys(config('social-publishing.channels')))]);
  $date=$v['date']??now('Europe/Amsterdam')->toDateString();$kind=$v['kind']??'daily';$channel=$this->channel($r);$cards=$planner->board($date);
  $historyDate=$v['history_date']??$date;$historyDay=$planner->day($historyDate);$history=SocialPublication::with('deliveries')->whereNotNull('first_shared_at')->whereHas('deliveries',fn($q)=>$q->where('status','published')->whereBetween('published_at',[$historyDay->utc(),$historyDay->endOfDay()->utc()])->when($r->filled('history_channel'),fn($q)=>$q->where('channel',$v['history_channel'])))->latest('first_shared_at')->paginate(10)->withQueryString();
  return view('social.manage',compact('date','kind','channel','cards','history','historyDate'));
 }
 public function prepare(Request $r,string $kind,Planner $planner){
  $this->admin($r);$v=$r->validate(['date'=>'required|date_format:Y-m-d','article_id'=>'nullable|integer|min:1']);abort_unless($v['date']===now('Europe/Amsterdam')->toDateString(),422,'Alleen voor vandaag kun je een ronde voorbereiden.');
  try{$p=$planner->prepare($v['date'],$kind,isset($v['article_id'])?(int)$v['article_id']:null);}catch(\RuntimeException $e){if($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)throw $e;report($e);return back()->withErrors(['social'=>'Er loopt al een selectie. Probeer het later opnieuw.']);}
  return redirect('/beheer/sociaal?'.http_build_query(['date'=>$v['date'],'kind'=>$kind]))->with('status',$p?'Voorbeeld en kanaalteksten klaargezet. Er is niets naar sociale kanalen verstuurd.':'Geen passend, nog niet gedeeld artikel beschikbaar.');
 }
 public function caption(Request $r,SocialPublication $publication,Planner $planner){$this->admin($r);$channel=$this->channel($r);$v=$r->validate(['caption'=>'required|string|max:2000']);$planner->saveCaption($publication,$channel,$v['caption']);return back()->with('status','Kanaaltekst opgeslagen.');}
 public function image(Request $r,Planner $planner,PostImage $renderer){
  $this->admin($r);$channel=$this->channel($r);$v=$r->validate(['publication'=>'nullable|integer|min:1','article'=>'nullable|integer|min:1','kind'=>'required|in:daily,reads,likes']);$snapshot=null;
  if(isset($v['publication'])){$p=SocialPublication::findOrFail($v['publication']);$a=$p->article;$snapshot=$p->snapshot;$kind=$p->kind;}else{$a=Article::findOrFail($v['article']??0);$kind=$v['kind'];}
  abort_unless($planner->usable($a),404);$bytes=$renderer->render($a,$channel,$kind,$snapshot);
  return response($bytes,200,['Content-Type'=>'image/jpeg','Content-Disposition'=>($r->boolean('download')?'attachment':'inline').'; filename="jouw-lichtpuntjes-'.$channel.'.jpg"','Cache-Control'=>'private, no-store']);
 }
 public function register(Request $r,SocialPublication $publication,Planner $planner){$this->admin($r);$channel=$this->channel($r);$v=$r->validate(['url'=>'required|string|max:2000','confirm'=>'accepted']);$planner->register($publication,$channel,$v['url'],$r->user());return back()->with('status','Postlink handmatig geregistreerd. Open de kanaallink om de echte plaatsing te controleren.');}
 public function checked(Request $r,SocialDelivery $delivery){$this->admin($r);abort_unless($delivery->external_url&&$delivery->status==='published',422);$delivery->update(['checked_at'=>now(),'checked_by'=>$r->user()->id]);DB::table('social_delivery_events')->insert(['delivery_id'=>$delivery->id,'user_id'=>$r->user()->id,'action'=>'manually_checked','url'=>null,'created_at'=>now()]);return back()->with('status','Je handmatige controle is vastgelegd.');}
}
