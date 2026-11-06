<?php
namespace App\Http\Controllers;
use App\Models\{BetaAnswer,BetaParticipant};
use App\Services\BetaTests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class BetaTestController extends Controller {
 private function person(Request $r):BetaParticipant {abort_unless(config('beta.enabled'),404);$p=app(BetaTests::class)->participant($r);abort_unless($p,403,'Open je persoonlijke testlink om verder te gaan.');return $p;}
 public function access(){app()->setLocale('nl');abort_unless(config('beta.enabled'),404);return view('beta.access');}
 public function enter(Request $r){
  abort_unless(config('beta.enabled'),404);$v=$r->validate(['code'=>'required|string|size:48|regex:/^[a-f0-9]+$/']);
  $p=BetaParticipant::where('token_hash',hash('sha256',$v['code']))->whereNull('revoked_at')->where('expires_at','>',now())->first();
  if(!$p)return back()->withErrors(['code'=>'Deze testcode is niet geldig of verlopen. Vraag Erik om een nieuwe link.']);
  $r->session()->regenerate();$r->session()->put(['beta.participant'=>$p->id,'beta.version'=>$p->access_version]);
  if(!$p->started_at)$p->update(['started_at'=>now()]);return redirect('/testgroep')->withCookie(cookie('beta_access',$p->id.'.'.$p->access_version,20160,'/',null,(bool)config('session.secure'),true,false,'lax'));
 }
 public function index(Request $r){
  app()->setLocale('nl');
  abort_unless(config('beta.enabled'),404);$p=app(BetaTests::class)->participant($r);if(!$p)return redirect('/testgroep/toegang');
  return view('beta.index',['person'=>$p,'tasks'=>BetaTests::TASKS,'answers'=>$p->answers->keyBy('task')]);
 }
 public function sessionInfo(Request $r){$p=$this->person($r);return response()->json(['csrf_token'=>csrf_token(),'participant_id'=>$p->id]);}
 public function save(Request $r,string $task){
  $p=$this->person($r);abort_unless($r->input('participant_id')===$p->id,403,'Dit testvenster hoort bij een andere testpersoon. Open je eigen testpagina opnieuw.');abort_unless(isset(BetaTests::TASKS[$task]),404);
  $rules=['outcome'=>'required|in:not_yet,yes,difficult,no,skipped','ease'=>'nullable|integer|min:1|max:5','device'=>'nullable|in:desktop,mobile,tablet','comment'=>'nullable|string|max:3000','page_path'=>['nullable','string','max:400','regex:~^/(?!/)[^\r\n?#]*$~'],'version'=>'required|integer|min:0|max:1000000'];
  if($task==='summary'){
   $rules['outcome']='required|in:not_yet,yes,skipped';
   $rules['summary']='required_if:outcome,yes|nullable|array:good,less_good,return_choice,return_reason,improvement';
   foreach(['good','less_good','return_reason'] as $field)$rules['summary.'.$field]='required_if:outcome,yes|nullable|string|max:3000';
   $rules['summary.return_choice']='required_if:outcome,yes|nullable|in:yes,maybe,no';
   $rules['summary.improvement']='nullable|string|max:3000';
  }
  $v=$r->validate($rules,[],['summary.good'=>'wat je goed vond','summary.less_good'=>'wat je minder goed vond','summary.return_choice'=>'of je terug zou komen','summary.return_reason'=>'waarom je wel of niet terug zou komen','summary.improvement'=>'je verbeteridee']);
  return DB::transaction(function()use($p,$task,$v){
   $locked=BetaParticipant::whereKey($p->id)->lockForUpdate()->firstOrFail();abort_if($locked->completed_at,409,'Je test is al afgerond.');
   $a=BetaAnswer::where('participant_id',$p->id)->where('task',$task)->first();
   if(($a?->version??0)!==$v['version'])return response()->json(['message'=>'Dit antwoord is inmiddels in een ander venster aangepast. Herlaad om die versie te bekijken.'],409);
   $values=collect($v)->except('version')->all();$values['version']=($a?->version??0)+1;
   $a=BetaAnswer::updateOrCreate(['participant_id'=>$p->id,'task'=>$task],$values);
   return response()->json(['version'=>$a->version,'saved_at'=>$a->updated_at->toIso8601String()]);
  });
 }
 public function complete(Request $r){
  $p=$this->person($r);abort_unless($r->input('participant_id')===$p->id,403,'Open je eigen testpagina opnieuw.');return DB::transaction(function()use($p){
   $p=BetaParticipant::whereKey($p->id)->lockForUpdate()->firstOrFail();
   abort_unless($p->answers()->whereIn('task',array_keys(BetaTests::TASKS))->whereIn('outcome',['yes','difficult','no','skipped'])->count()===count(BetaTests::TASKS),422,'Geef eerst bij iedere opdracht aan of je die hebt gedaan of overgeslagen.');
   if(!$p->completed_at)$p->update(['completed_at'=>now()]);return response()->json(['message'=>'Dank je wel! Je antwoorden zijn opgeslagen.']);
  });
 }
 private function admin(Request $r):void {abort_unless($r->user()?->is_admin&&!$r->user()->is_suspended&&$r->user()->hasVerifiedEmail(),403);}
 public function overview(Request $r){$this->admin($r);app()->setLocale('nl');return view('beta.admin',['people'=>BetaParticipant::with('answers')->oldest()->get(),'tasks'=>BetaTests::TASKS]);}
 public function invite(Request $r){$this->admin($r);$v=$r->validate(['label'=>'required|string|max:80']);[$p,$code]=app(BetaTests::class)->invite($v['label']);return back()->with('beta_invite',url('/testgroep/toegang').'#'.$code);}
 public function reissue(Request $r,BetaParticipant $person){
  $this->admin($r);$code=bin2hex(random_bytes(24));
  DB::transaction(function()use($person,$code){$p=BetaParticipant::whereKey($person->id)->lockForUpdate()->firstOrFail();$p->update(['token_hash'=>hash('sha256',$code),'access_version'=>$p->access_version+1,'expires_at'=>now()->addDays(14),'revoked_at'=>null]);});
  return back()->with('beta_invite',url('/testgroep/toegang').'#'.$code);
 }
 public function revoke(Request $r,BetaParticipant $person){$this->admin($r);$person->update(['revoked_at'=>now(),'access_version'=>$person->access_version+1]);return back()->with('status','Testtoegang ingetrokken.');}
 public function export(Request $r){
  $this->admin($r);$people=BetaParticipant::with('answers')->oldest()->get();
  return response()->streamDownload(function()use($people){$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['Testpersoon','Opdracht','Uitkomst','Gemak 1-5','Apparaat','Toelichting','Paginapad','Opgeslagen','Test afgerond','Wat vond je goed?','Wat vond je minder goed?','Zou je terugkomen?','Waarom wel / misschien / niet?','Verbeteridee'],';');foreach($people as $p)foreach($p->answers as $a)fputcsv($out,array_map(BetaTests::csvCell(...),[$p->label,BetaTests::TASKS[$a->task]['title']??$a->task,$a->outcome,$a->ease,$a->device,$a->comment,$a->page_path,$a->updated_at?->toIso8601String(),$p->completed_at?->toIso8601String(),$a->summary['good']??'', $a->summary['less_good']??'', ['yes'=>'Ja','maybe'=>'Misschien','no'=>'Nee'][$a->summary['return_choice']??'']??'', $a->summary['return_reason']??'', $a->summary['improvement']??'']),';');fclose($out);},'testgroep-antwoorden.csv',['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'private, no-store']);
 }
}
