<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Comment;
use App\Models\LightspotSubmission;
use App\Models\ProfilePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class UserAdminController extends Controller {
 private function owner(Request $r):void {abort_unless($r->user()?->is_admin,403);}
 public function index(Request $r){
  $this->owner($r);$v=$r->validate(['q'=>'nullable|string|max:100','filter'=>'nullable|in:all,active,suspended,photos,moments,privacy','handled'=>'nullable|in:open,all']);$search=$v['q']??'';$filter=$v['filter']??'all';
  if($filter==='privacy') {
   $requests=DB::table('privacy_requests')->leftJoin('users','users.id','=','privacy_requests.user_id')->select('privacy_requests.*','users.name','users.email');
   if(($v['handled']??'open')==='open')$requests->whereNull('resolved_at');
   if($search!=='')$requests->where(fn($q)=>$q->where('users.name','like','%'.$search.'%')->orWhere('users.email','like','%'.$search.'%'));
   return view('admin.requests',['requests'=>$requests->orderByDesc('privacy_requests.id')->paginate(15)->withQueryString(),'search'=>$search]);
  }
  $query=User::with('profilePhoto')->withCount(['comments','submissions']);
  if($search!=='')$query->where(fn($q)=>$q->where('name','like','%'.$search.'%')->orWhere('email','like','%'.$search.'%'));
  if(in_array($filter,['active','suspended'],true))$query->where('is_suspended',$filter==='suspended');
  if($filter==='photos')$query->whereHas('photos',fn($q)=>$q->whereIn('status',['pending','doubt']));
  if($filter==='moments')$query->whereHas('moments',fn($q)=>$q->where('visibility','public')->whereIn('status',['pending','doubt']));
  return view('community.users-admin',['users'=>$query->latest('id')->paginate(30)->withQueryString(),'search'=>$search,'filter'=>$filter,'photoCount'=>ProfilePhoto::whereIn('status',['pending','doubt'])->count(),'requestCount'=>DB::table('privacy_requests')->whereNull('resolved_at')->count()]);
 }
 public function show(Request $r,User $user){
  $this->owner($r);$v=$r->validate(['section'=>'nullable|in:overview,photos,moments,comments,stories,activity,moderation,privacy,donations']);$section=$v['section']??'overview';$user->load('profilePhoto')->loadCount(['comments','submissions','moments'=>fn($q)=>$q->where('visibility','public')]);$id=$user->id;
  $mode=app(\App\Services\SupportPayments::class)->mode();
  $donations=\App\Models\SupportPayment::whereHas('pledge',fn($q)=>$q->where('user_id',$id)->where('mode',$mode))->where('status','paid')->whereNotNull('paid_at')->selectRaw('COUNT(*) as payments, COALESCE(SUM(CASE WHEN cents > refunded_cents + charged_back_cents THEN cents - refunded_cents - charged_back_cents ELSE 0 END),0) as net')->first();
  return view('admin.member',[
   'section'=>$section,'mode'=>$mode,'donations'=>$donations,
   'requests'=>DB::table('privacy_requests')->where('user_id',$id)->orderByDesc('id')->paginate(10,['*'],'requests_page')->withQueryString(),
   'member'=>$user,'publicMoments'=>\App\Models\PositiveMoment::where('user_id',$id)->where('visibility','public')->latest('id')->paginate(10,['*'],'moments_page')->withQueryString(),'photos'=>ProfilePhoto::where('user_id',$id)->latest('id')->limit(5)->get(),
   'comments'=>Comment::with('article')->where('user_id',$id)->latest('id')->paginate(15,['*'],'comments_page')->withQueryString(),
   'submissions'=>LightspotSubmission::where('user_id',$id)->latest('id')->paginate(10,['*'],'stories_page')->withQueryString(),
   'likes'=>DB::table('user_article_likes')->join('articles','articles.id','=','user_article_likes.article_id')->where('user_id',$id)->select('articles.id','articles.source_title','user_article_likes.created_at')->orderByDesc('user_article_likes.created_at')->paginate(10,['*'],'likes_page')->withQueryString(),
   'events'=>DB::table('lightpoint_events')->where('user_id',$id)->latest('id')->paginate(20,['*'],'events_page')->withQueryString(),
   'moderation'=>DB::table('user_moderation_events')->leftJoin('users','users.id','=','user_moderation_events.moderator_id')->where('user_id',$id)->select('user_moderation_events.*','users.name as moderator_name')->orderByDesc('user_moderation_events.id')->paginate(10,['*'],'moderation_page')->withQueryString(),
   'points'=>DB::table('lightpoint_events')->where('user_id',$id)->where('active',true)->sum('points'),
  ]);
 }
}
