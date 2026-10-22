<?php
namespace App\Services;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;
class Lightpoints {
 public function award(User $user,string $reference,string $kind,int $points,string $label,int $dailyCap=0):bool {
  if(!$user->hasVerifiedEmail()||$user->is_suspended)return false;
  return DB::transaction(function()use($user,$reference,$kind,$points,$label,$dailyCap){
   $current=User::whereKey($user->id)->lockForUpdate()->firstOrFail();if(!$current->hasVerifiedEmail()||$current->is_suspended)return false;
   app(ActivityCalendar::class)->record($current,$kind,$reference);
   if(DB::table('lightpoint_events')->where('user_id',$user->id)->where('reference',$reference)->exists())return false;
   $period=now('Europe/Amsterdam')->toDateString();
   if($dailyCap&&DB::table('lightpoint_events')->where('user_id',$user->id)->where('kind',$kind)->where('period',$period)->count()>=$dailyCap)return false;
   DB::table('lightpoint_events')->insert(['user_id'=>$user->id,'reference'=>$reference,'kind'=>$kind,'points'=>$points,'label'=>$label,'period'=>$period,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);return true;
  },3);
 }
 public function comment(Comment $comment):void {
  $user=$comment->user;if(!$user->hasVerifiedEmail()||$user->is_suspended)return;
  $this->award($user,'comment:'.$comment->id,'comment',1,'Een vriendelijke reactie',5);
  if($user->hasVerifiedEmail()&&!$user->is_suspended)DB::table('lightpoint_events')->where('user_id',$user->id)->where('reference','comment:'.$comment->id)->update(['active'=>true,'updated_at'=>now()]);
  // Referral is earned only after the invited, verified account contributes positively.
  if(!$user->referred_by)return;
  $existing=DB::table('lightpoint_events')->where('user_id',$user->referred_by)->where('reference','invite:'.$user->id)->first();
  if($existing){$ref=User::find($user->referred_by);if($ref&&$ref->hasVerifiedEmail()&&!$ref->is_suspended)DB::table('lightpoint_events')->where('id',$existing->id)->update(['active'=>true,'updated_at'=>now()]);return;}
  if(Comment::where('user_id',$user->id)->where('status','approved')->where('id','!=',$comment->id)->exists())return;
  $referrer=User::find($user->referred_by);if(!$referrer||$referrer->id===$user->id)return;
  User::whereKey($referrer->id)->lockForUpdate()->firstOrFail();
  $used=DB::table('lightpoint_events')->where('user_id',$referrer->id)->where('kind','invite')->where('period','>=',now('Europe/Amsterdam')->startOfMonth()->toDateString())->count();
  if($used<5)$this->award($referrer,'invite:'.$user->id,'invite',5,'Een vriend bracht een eerste glimlach');
 }
 public function revokeComment(Comment $c):void {
  DB::table('lightpoint_events')->where('user_id',$c->user_id)->where('reference','comment:'.$c->id)->update(['active'=>false,'updated_at'=>now()]);
  if($c->user?->referred_by&&!Comment::where('user_id',$c->user_id)->where('status','approved')->where('id','!=',$c->id)->exists())DB::table('lightpoint_events')->where('user_id',$c->user->referred_by)->where('reference','invite:'.$c->user_id)->update(['active'=>false,'updated_at'=>now()]);
 }
 public function summary(User $user):array {
  $points=(int)DB::table('lightpoint_events')->where('user_id',$user->id)->where('active',true)->sum('points');
  $stages=BadgeCatalog::stages();
  $current=$stages[0];$next=null;foreach($stages as $s){if($points>=$s['points'])$current=$s;elseif(!$next)$next=$s;}
  return ['points'=>$points,'current'=>$current,'next'=>$next,'stages'=>$stages,'earned_count'=>count(array_filter($stages,fn($b)=>$b['points']<=$points)),'total_badges'=>count($stages),'progress'=>$next?min(100,round(100*($points-$current['points'])/($next['points']-$current['points']))):100];
 }
}
