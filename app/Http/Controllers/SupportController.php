<?php
namespace App\Http\Controllers;
use App\Models\{SupportPledge,SupportPayment};
use App\Services\SupportPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{Cache,Schema};
class SupportController extends Controller {
 public function index(Request $r,SupportPayments $service){
  app(\App\Services\SupportInvitations::class)->pause($r,90);
  $attempt=(string)Str::uuid();$r->session()->put('support_attempt_'.$attempt,true);
  $initialFrequency=in_array($r->query('frequency'),['once','monthly'],true)?$r->query('frequency'):'once';
  return view('support.index',['ready'=>$service->ready(),'attempt'=>$attempt,'initialFrequency'=>$initialFrequency]);
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
 public function thanks(Request $r,string $id,SupportPayments $service){
  abort_unless(Str::isUuid($id),404);$p=SupportPledge::findOrFail($id);$problem=false;
  if($p->first_payment_id)try{$service->sync($p->first_payment_id);}catch(\Throwable $e){$problem=true;}
  $p->refresh();
  $confirmed=!$problem&&$p->status==='paid'&&$p->payments()->where('provider_id',$p->first_payment_id)->where('status','paid')->whereNotNull('paid_at')->whereRaw('cents > refunded_cents + charged_back_cents')->exists();
  $ownReturn=$r->session()->get('support_attempt_'.$id)||($r->user()&&$p->user_id===$r->user()->id);
  if($confirmed&&$ownReturn&&$p->frequency==='once')app(\App\Services\SupportInvitations::class)->pause($r,180);
  $showThanks=$confirmed&&$ownReturn&&!$r->session()->get('support_thanked_'.$id);
  if($showThanks)$r->session()->put('support_thanked_'.$id,true);
  return view('support.thanks',['pledge'=>$p,'problem'=>$problem,'showThanks'=>(bool)$showThanks]);
 }
 public function webhook(Request $r,SupportPayments $service){
  $v=$r->validate(['id'=>['required','string','regex:/^tr_[A-Za-z0-9]+$/','max:100']]);
  try{$service->sync($v['id']);}catch(\Throwable $e){return response('Retry later',503);}return response('OK');
 }
 public function mine(Request $r){
  $mode=app(SupportPayments::class)->mode();
  $owned=fn($q)=>$q->where('user_id',$r->user()->id)->where('mode',$mode);
  $payments=SupportPayment::whereHas('pledge',$owned);
  $totals=(clone $payments)->where('status','paid')->whereNotNull('paid_at')->selectRaw('COUNT(*) as paid_count, COALESCE(SUM(CASE WHEN cents > refunded_cents + charged_back_cents THEN cents - refunded_cents - charged_back_cents ELSE 0 END),0) as net_cents, COALESCE(SUM(refunded_cents + charged_back_cents),0) as adjusted_cents')->first();
  $hasPending=(clone $payments)->where(fn($q)=>$q->whereIn('status',['pending','open','authorized'])->orWhere(fn($q)=>$q->where('status','paid')->whereNull('paid_at')))->exists()||SupportPledge::where('user_id',$r->user()->id)->where('mode',$mode)->whereNull('stopped_at')->whereIn('status',['pending','open'])->whereDoesntHave('payments')->exists();
  $monthlyPledges=SupportPledge::where('user_id',$r->user()->id)->where('mode',$mode)->where('frequency','monthly')->whereNull('stopped_at')->where(fn($q)=>$q->whereNull('subscription_status')->orWhereNotIn('subscription_status',['canceled','completed']))->where(fn($q)=>$q->whereIn('status',['paid','pending','open'])->orWhereIn('subscription_status',['active','pending','suspended']))->latest()->get();
  $monthlyCents=(int)$monthlyPledges->where('subscription_status','active')->sum('cents');
  $history=(clone $payments)->with('pledge')->orderByRaw('COALESCE(paid_at,created_at) DESC')->orderByDesc('id')->paginate(10,['*'],'payments_page')->appends(['lang'=>app()->getLocale()]);
  $attempts=SupportPledge::where('user_id',$r->user()->id)->where('mode',$mode)->whereDoesntHave('payments')->latest()->paginate(5,['*'],'attempts_page')->appends(['lang'=>app()->getLocale()]);
  return view('support.mine',['history'=>$history,'attempts'=>$attempts,'monthlyPledges'=>$monthlyPledges,'monthlyCents'=>$monthlyCents,'netCents'=>(int)$totals->net_cents,'paidCount'=>(int)$totals->paid_count,'adjustedCents'=>(int)$totals->adjusted_cents,'mode'=>$mode,'hasPending'=>$hasPending,'hasPaid'=>(int)$totals->paid_count>0]);
 }
 public function stop(Request $r,SupportPledge $pledge,SupportPayments $service){
  abort_unless($pledge->user_id===$r->user()->id&&$pledge->frequency==='monthly',403);
  $r->validate(['confirm'=>'accepted']);
  try{$service->stop($pledge);}catch(\Throwable $e){return back()->withErrors(['stop'=>app()->getLocale()==='en'?'Stopping is not confirmed yet. Please try again or contact me.':'Stoppen is nog niet bevestigd. Probeer opnieuw of neem contact met mij op.']);}
  return back()->with('status',app()->getLocale()==='en'?'Your monthly contribution has stopped. A collection already in progress may still be processed. Thank you for your support!':'Je maandelijkse bijdrage is gestopt. Een al ingezette incasso kan nog worden verwerkt. Bedankt voor je steun!');
 }
 public function admin(Request $r){
  abort_unless($r->user()?->is_admin,403);
  return redirect('/beheer/donaties?lang='.app()->getLocale());
 }
}
