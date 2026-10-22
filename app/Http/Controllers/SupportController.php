<?php
namespace App\Http\Controllers;
use App\Models\{SupportPledge,SupportPayment};
use App\Services\SupportPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{Cache,Schema};
class SupportController extends Controller {
 public function index(Request $r,SupportPayments $service){
  $attempt=(string)Str::uuid();$r->session()->put('support_attempt_'.$attempt,true);
  return view('support.index',['ready'=>$service->ready(),'attempt'=>$attempt]);
 }
 public function pay(Request $r,SupportPayments $service){
  abort_unless($service->ready(),503,'Bijdragen zijn nog niet beschikbaar.');
  $v=$r->validate(['amount'=>'required|string|max:10','frequency'=>'required|in:once,monthly','consent'=>'accepted_if:frequency,monthly','attempt'=>'required|uuid']);
  try{$cents=SupportPayments::cents($v['amount']);}catch(\InvalidArgumentException $e){return back()->withErrors(['amount'=>$e->getMessage()])->withInput();}
  abort_unless($r->session()->get('support_attempt_'.$v['attempt']),419);
  if($v['frequency']==='monthly'&&!$r->user()){$r->session()->put('url.intended',url('/steun'));return redirect('/inloggen')->with('status','Log in om je maandelijkse bijdrage zelf te kunnen beheren.');}
  if($v['frequency']==='monthly')abort_unless($r->user()->hasVerifiedEmail()&&!$r->user()->is_suspended,403,'Bevestig eerst je e-mailadres.');
  try {
   $url=Cache::lock('support-start-'.$v['attempt'],120)->block(5,function()use($v,$r,$cents,$service){
    $p=SupportPledge::firstOrCreate(['id'=>$v['attempt']],['user_id'=>$r->user()?->id,'cents'=>$cents,'frequency'=>$v['frequency'],'mode'=>$service->mode(),'consented_at'=>$v['frequency']==='monthly'?now():null]);
    abort_if($p->cents!==$cents||$p->frequency!==$v['frequency'],422,'Dit betaalverzoek is al gebruikt. Open de bijdragepagina opnieuw.');
    return $service->checkout($p);
   });return redirect()->away($url,303);
  }catch(\Illuminate\Http\Exceptions\HttpResponseException|\Symfony\Component\HttpKernel\Exception\HttpException $e){throw $e;}
  catch(\Throwable $e){return back()->withErrors(['payment'=>'Betalen lukt nu niet. Probeer het later opnieuw.'])->withInput();}
 }
 public function thanks(string $id,SupportPayments $service){
  abort_unless(Str::isUuid($id),404);$p=SupportPledge::findOrFail($id);$problem=false;
  if($p->first_payment_id)try{$service->sync($p->first_payment_id);}catch(\Throwable $e){$problem=true;}
  return view('support.thanks',['pledge'=>$p->fresh(),'problem'=>$problem]);
 }
 public function webhook(Request $r,SupportPayments $service){
  $v=$r->validate(['id'=>['required','string','regex:/^tr_[A-Za-z0-9]+$/','max:100']]);
  try{$service->sync($v['id']);}catch(\Throwable $e){return response('Retry later',503);}return response('OK');
 }
 public function mine(Request $r){
  return view('support.mine',['pledges'=>SupportPledge::where('user_id',$r->user()->id)->latest()->paginate(15)]);
 }
 public function stop(Request $r,SupportPledge $pledge,SupportPayments $service){
  abort_unless($pledge->user_id===$r->user()->id&&$pledge->frequency==='monthly',403);
  $r->validate(['confirm'=>'accepted']);
  try{$service->stop($pledge);}catch(\Throwable $e){return back()->withErrors(['stop'=>'Stoppen is nog niet bevestigd. Probeer opnieuw of neem contact met ons op.']);}
  return back()->with('status','Je maandelijkse bijdrage is gestopt. Een al ingezette incasso kan nog worden verwerkt. Bedankt voor je steun!');
 }
 public function admin(Request $r){
  abort_unless($r->user()?->is_admin,403);
  return view('support.admin',['pledges'=>SupportPledge::with('user')->withSum(['payments as paid_cents'=>fn($q)=>$q->where('status','paid')],'cents')->latest()->paginate(30),
   'totals'=>SupportPayment::whereHas('pledge',fn($q)=>$q->where('mode',app(SupportPayments::class)->mode()))->selectRaw("COALESCE(SUM(CASE WHEN status='paid' THEN cents ELSE 0 END),0) as gross, COALESCE(SUM(refunded_cents),0) as refunds, COALESCE(SUM(charged_back_cents),0) as chargebacks")->first()]);
 }
}
