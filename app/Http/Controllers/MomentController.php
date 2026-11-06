<?php
namespace App\Http\Controllers;
use App\Models\PositiveMoment;
use App\Jobs\ReviewMoment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
class MomentController extends Controller {
 public function feed(Request $r){$moments=PositiveMoment::publicVisible()->with('user.profilePhoto')->withCount(['hearts'=>fn($q)=>$q->where('is_suspended',false)])->latest('id')->paginate(15)->withQueryString();$likedMomentIds=$r->user()?DB::table('moment_hearts')->where('user_id',$r->user()->id)->whereIn('moment_id',$moments->getCollection()->pluck('id'))->pluck('moment_id')->all():[];return view('community.public-moments',compact('moments','likedMomentIds'));}
 public function choose(){return view('community.share-choice');}
 public function index(Request $r){
  $v=$r->validate(['month'=>'nullable|date_format:Y-m','day'=>'nullable|date_format:Y-m-d']);
  $month=isset($v['month'])?CarbonImmutable::createFromFormat('!Y-m',$v['month'],'Europe/Amsterdam'):CarbonImmutable::now('Europe/Amsterdam')->startOfMonth();$u=$r->user();
  $today=now('Europe/Amsterdam')->toDateString();$day=$v['day']??($month->format('Y-m')===substr($today,0,7)?$today:$month->toDateString());
  abort_unless(substr($day,0,7)===$month->format('Y-m'),422);
  return view('community.moments',['moments'=>PositiveMoment::where('user_id',$u->id)->when(isset($v['day']),fn($q)=>$q->whereDate('moment_date',$v['day']))->whereBetween('moment_date',[$month->toDateString(),$month->endOfMonth()->toDateString()])->latest('id')->paginate(15)->withQueryString(),'selectedDay'=>$v['day']??null,'month'=>$month]);
 }
 public function store(Request $r){
  $v=$this->validateMoment($r);$image=$r->boolean('image_processing');
  $m=PositiveMoment::create(['user_id'=>$r->user()->id,'body'=>$v['body'],'visibility'=>$v['visibility'],'moment_date'=>now('Europe/Amsterdam')->toDateString(),'ai_accepted_at'=>now(),'image_accepted_at'=>$image?now():null,'image_state'=>$image?'queued':null]);
  $this->dispatch($m);return redirect('/mijn-momentjes?lang='.app()->getLocale())->with('status',app()->getLocale()==='en'?'Saved privately for review.':'Privé opgeslagen voor controle.');
 }
 public function update(Request $r,PositiveMoment $moment){
  abort_unless($moment->user_id===$r->user()->id,403);$v=$this->validateMoment($r);$oldPath=null;
  $m=DB::transaction(function()use($moment,$v,$r,&$oldPath){
   $m=PositiveMoment::whereKey($moment->id)->lockForUpdate()->firstOrFail();$oldPath=$m->image_path;
   DB::table('moment_hearts')->where('moment_id',$m->id)->delete();
   $m->update(['body'=>$v['body'],'visibility'=>$v['visibility'],'status'=>'pending','reason'=>null,'translations'=>null,'translation_hash'=>null,'translation_state'=>'pending','source_language'=>null,'revision'=>$m->revision+1,'moderated_by'=>null,'ai_accepted_at'=>now(),'image_accepted_at'=>$r->boolean('image_processing')?now():null,'image_state'=>$r->boolean('image_processing')?'queued':null,'image_path'=>null,'image_style'=>null,'image_subject'=>null,'image_revision'=>null]);return $m;
  });
  \App\Services\MomentImageFiles::remove($oldPath);app(\App\Services\MomentModerator::class)->syncPoints($m);$this->dispatch($m);
  return back()->with('status',app()->getLocale()==='en'?'Changes saved for a new review.':'Aanpassing opgeslagen voor een nieuwe controle.');
 }
 public function destroy(Request $r,PositiveMoment $moment){abort_unless($moment->user_id===$r->user()->id,403);DB::transaction(function()use($moment){$m=PositiveMoment::whereKey($moment->id)->lockForUpdate()->firstOrFail();$day=$m->moment_date->toDateString();$m->delete();if(!PositiveMoment::where('user_id',$m->user_id)->whereDate('moment_date',$day)->where('status','approved')->exists())DB::table('lightpoint_events')->where('user_id',$m->user_id)->where('reference','moment:'.$day)->update(['active'=>false]);});return back()->with('status',app()->getLocale()==='en'?'Moment removed.':'Momentje verwijderd.');}
 private function validateMoment(Request $r):array {return $r->validate(['body'=>'required|string|min:3|max:500','visibility'=>'required|in:private,public','ai_processing'=>'accepted','image_processing'=>'sometimes|accepted','website'=>'prohibited']);}
 private function dispatch(PositiveMoment $m):void {try{ReviewMoment::dispatch($m->id,$m->revision);}catch(\Throwable $e){$m->update(['status'=>'doubt','reason'=>['nl'=>'De controle kon niet starten. Probeer later opnieuw.','en'=>'Review could not start. Please retry later.']]);}}
 public function heart(Request $r,PositiveMoment $moment){
  $v=$r->validate(['liked'=>'required|boolean']);abort_if($r->user()->id===$moment->user_id,422,'Een hartje is voor iemand anders.');
  $stats=DB::transaction(function()use($r,$moment,$v){
   $u=\App\Models\User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();abort_if($u->is_suspended,403);abort_unless($u->community_accepted_at,403);
   $m=PositiveMoment::whereKey($moment->id)->publicVisible()->lockForUpdate()->first();abort_unless($m,404);$awarded=false;
   if($v['liked']){
    DB::table('moment_hearts')->insertOrIgnore(['moment_id'=>$m->id,'user_id'=>$u->id,'created_at'=>now()]);
    $awarded=app(\App\Services\Lightpoints::class)->award($u,'heart:moment:'.now('Europe/Amsterdam')->toDateString().':'.$m->id,'heart',1,'Je gaf wat liefde met een hartje');
   }else DB::table('moment_hearts')->where('moment_id',$m->id)->where('user_id',$u->id)->delete();
   return ['moment_id'=>(string)$m->id,'liked'=>(bool)$v['liked'],'hearts_count'=>$m->hearts()->where('is_suspended',false)->count(),'awarded'=>$awarded,'points_awarded'=>$awarded?1:0];
  },3);
  if($r->expectsJson())return response()->json($stats)->header('Cache-Control','private, no-store');
  return back()->withFragment('moment-'.$moment->id);
 }
 public function moderate(Request $r,PositiveMoment $moment){abort_unless($r->user()?->is_admin,403);abort_unless($moment->visibility==='public',404);$v=$r->validate(['decision'=>'required|in:approved,rejected','note'=>'required|string|min:3|max:1000']);DB::transaction(function()use($moment,$v,$r){$m=PositiveMoment::whereKey($moment->id)->lockForUpdate()->firstOrFail();abort_unless($m->visibility==='public'&&!$m->user->is_suspended,422);$m->update(['status'=>$v['decision'],'reason'=>['nl'=>$v['note'],'en'=>$v['note']],'moderated_by'=>$r->user()->id]);app(\App\Services\MomentModerator::class)->syncPoints($m);});\App\Services\ContributionTranslator::queue('moment',$moment->id);\App\Services\MomentIllustrator::queue($moment->fresh());return back()->with('status','Beslissing opgeslagen.');}
}
