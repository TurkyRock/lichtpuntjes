<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\SupportPledge;
use App\Services\{DetachedSupport,SupportPayments};
class DetachedSupportController extends Controller {
 public function requestForm(){return view('support.manage-request');}
 public function requestLink(Request $r){$v=$r->validate(['email'=>'required|email|max:254','website'=>'prohibited']);
  foreach(SupportPledge::whereNull('user_id')->whereNull('stopped_at')->where('management_email_hash',DetachedSupport::emailHash($v['email']))->limit(10)->get() as $p){try{app(DetachedSupport::class)->sendLink($p,$p->management_email);}catch(\Throwable $e){/* Do not disclose whether the address has a donation. */}}
  return back()->with('status',app()->getLocale()==='en'?'If this address has ongoing support without an account, you will receive a management link. Contact us if it does not arrive.':'Als dit adres doorlopende steun zonder account heeft, ontvang je een beheerlink. Mail ons als die niet aankomt.');
 }
 public function show(Request $r,SupportPledge $pledge){abort_unless(!$pledge->user_id&&$pledge->detached_at,404);return response()->view('support.manage-detached',compact('pledge'))->header('Cache-Control','private, no-store')->header('Referrer-Policy','no-referrer')->header('X-Robots-Tag','noindex, nofollow');}
 public function stop(Request $r,SupportPledge $pledge){abort_unless(!$pledge->user_id&&$pledge->detached_at,404);$r->validate(['confirm'=>'accepted']);try{app(SupportPayments::class)->stop($pledge);$pledge->update(['management_email'=>null,'management_email_hash'=>null]);}catch(\Throwable $e){return back()->withErrors(['stop'=>'Stoppen is nog niet bevestigd. Probeer opnieuw of mail '.config('privacy.email')]);}return redirect('/steun/beheren')->with('status','Je maandelijkse steun is gestopt. Een al ingezette incasso kan nog worden verwerkt.');}
}
