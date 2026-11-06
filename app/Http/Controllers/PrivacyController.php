<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PrivacyController extends Controller {
 public function index(Request $r){return view('community.privacy-requests',['requests'=>DB::table('privacy_requests')->where('user_id',$r->user()->id)->latest()->paginate(10),'pendingEmailChange'=>DB::table('account_email_changes')->where('user_id',$r->user()->id)->where('expires_at','>',now())->where('security_version',(int)$r->user()->security_version)->first(),'securityEvents'=>DB::table('account_security_events')->where('user_id',$r->user()->id)->latest('id')->limit(15)->get(),'admin'=>false]);}
 public function store(Request $r){$v=$r->validate(['kind'=>'required|in:access,correction,deletion,objection,other','message'=>'required|string|min:5|max:2000','website'=>'prohibited']);DB::table('privacy_requests')->insert(['user_id'=>$r->user()->id,'kind'=>$v['kind'],'message'=>$v['message'],'created_at'=>now(),'updated_at'=>now()]);return back()->with('status',app()->getLocale()==='en'?'Request received. The administrator will review it.':'Verzoek ontvangen. De beheerder behandelt het persoonlijk.');}
 public function admin(Request $r){abort_unless($r->user()->is_admin,403);return redirect('/beheer/gebruikers?filter=privacy&lang='.app()->getLocale());}
 public function resolve(Request $r,int $id){abort_unless($r->user()->is_admin,403);$r->validate(['handled'=>'accepted']);abort_unless(DB::table('privacy_requests')->where('id',$id)->exists(),404);DB::table('privacy_requests')->where('id',$id)->update(['resolved_at'=>now(),'updated_at'=>now()]);return back()->with('status','Verzoek gemarkeerd als afgehandeld.');}
}
