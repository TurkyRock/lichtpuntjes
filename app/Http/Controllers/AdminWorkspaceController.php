<?php
namespace App\Http\Controllers;
use App\Models\{PositiveMoment,ProfilePhoto};
use App\Services\AdminOverview;
use Illuminate\Http\Request;
class AdminWorkspaceController extends Controller {
 public function home(Request $r,AdminOverview $overview) {
  abort_unless($r->user()?->is_admin,403);
  // Bestaande bladwijzers naar een reactiebak blijven bruikbaar.
  if($r->hasAny(['status','q']))return app(CommunityAdminController::class)->index($r);
  return view('admin.overview',['counts'=>$overview->counts(),'system'=>$overview->system()]);
 }
 public function moments(Request $r) {
  abort_unless($r->user()?->is_admin,403);$v=$r->validate(['status'=>'nullable|in:review,approved,rejected','q'=>'nullable|string|max:100']);$filter=$v['status']??'review';
  $q=PositiveMoment::with('user')->where('visibility','public');
  $filter==='review'?$q->whereIn('status',['pending','doubt']):$q->where('status',$filter);
  // Momentteksten zijn versleuteld. Zoek alleen op de naam van de schrijver.
  if(!empty($v['q']))$q->whereHas('user',fn($u)=>$u->where('name','like','%'.$v['q'].'%'));
  return view('admin.moments',['moments'=>$q->latest('id')->paginate(15)->withQueryString(),'filter'=>$filter]);
 }
 public function photos(Request $r) {
  abort_unless($r->user()?->is_admin,403);$v=$r->validate(['status'=>'nullable|in:review,approved,rejected']);$filter=$v['status']??'review';
  $q=ProfilePhoto::with('user');$filter==='review'?$q->whereIn('status',['pending','doubt']):$q->where('status',$filter);
  return view('admin.photos',['photos'=>$q->latest('id')->paginate(15)->withQueryString(),'filter'=>$filter]);
 }
}
