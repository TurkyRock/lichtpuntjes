<?php
namespace App\Http\Controllers;
use App\Services\SupportInvitations;
use Illuminate\Http\Request;
class SupportInvitationController extends Controller {
 public function shown(Request $r,SupportInvitations $invitations){$v=$r->validate(['kind'=>'required|in:inline,popup','placement'=>'required|in:home,account']);abort_if($v['placement']==='account'&&!$r->user(),403);return response()->json(['show'=>$invitations->claim($r,$v['kind'],$v['placement'])])->header('Cache-Control','private, no-store');}
 public function dismiss(Request $r,SupportInvitations $invitations){$invitations->pause($r);return response()->json(['saved'=>true])->header('Cache-Control','private, no-store');}
}
