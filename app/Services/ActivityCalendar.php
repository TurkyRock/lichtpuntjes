<?php
namespace App\Services;
use App\Models\{User,PositiveMoment,Comment,Article};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
class ActivityCalendar {
 public function visit(User $user):void {
  if(!$user->hasVerifiedEmail()||$user->is_suspended||!$user->community_accepted_at)return;
  if(DB::table('user_visits')->where('user_id',$user->id)->where('visit_date',now('Europe/Amsterdam')->toDateString())->exists())return;
  DB::transaction(function()use($user){
   $u=User::whereKey($user->id)->lockForUpdate()->firstOrFail();
   if(!$u->hasVerifiedEmail()||$u->is_suspended||!$u->community_accepted_at)return;
   $day=now('Europe/Amsterdam')->toDateString();
   $prior=DB::table('user_visits')->where('user_id',$u->id)->where('visit_date','<',$day)->exists();
   $added=DB::table('user_visits')->insertOrIgnore(['user_id'=>$u->id,'visit_date'=>$day]);
   if($added&&$prior)app(Lightpoints::class)->award($u,'return:'.$day,'return',1,'Fijn dat je er weer bent!');
  },3);
 }
 public function record(User $user,string $kind,string $reference):void {
  if(!$user->hasVerifiedEmail()||$user->is_suspended||!$user->community_accepted_at||!in_array($kind,['read','share','heart'],true))return;
  DB::table('user_activities')->insertOrIgnore(['user_id'=>$user->id,'activity_date'=>now('Europe/Amsterdam')->toDateString(),'kind'=>$kind,'reference'=>$reference]);
 }
 public function month(User $user,CarbonImmutable $month,string $selected):array {
  $start=$month->startOfMonth();$end=$start->endOfMonth();$from=$start->toDateString();$to=$end->toDateString();$days=[];
  $add=function(string $day,string $kind,int $count=1)use(&$days){$days[$day]['activities'][$kind]=($days[$day]['activities'][$kind]??0)+$count;};
  foreach(DB::table('user_visits')->where('user_id',$user->id)->whereBetween('visit_date',[$from,$to])->pluck('visit_date') as $day)$days[$day]['visited']=true;
  $rows=DB::table('user_activities')->where('user_id',$user->id)->whereBetween('activity_date',[$from,$to])->select('activity_date','kind')->selectRaw('COUNT(*) AS amount')->groupBy('activity_date','kind')->get();
  foreach($rows as $row)$add($row->activity_date,$row->kind,(int)$row->amount);
  // Earlier points records provide a truthful partial history; never infer visits.
  $history=DB::table('lightpoint_events as e')->where('e.user_id',$user->id)->where('e.active',true)->whereBetween('e.period',[$from,$to])->whereIn('e.kind',['read','share','heart','invite'])->whereNotExists(function($q){$q->selectRaw('1')->from('user_activities as a')->whereColumn('a.user_id','e.user_id')->whereColumn('a.activity_date','e.period')->whereColumn('a.kind','e.kind')->whereColumn('a.reference','e.reference');})->select('e.period','e.kind')->selectRaw('COUNT(*) AS amount')->groupBy('e.period','e.kind')->get();
  foreach($history as $row)$add($row->period,$row->kind,(int)$row->amount);
  foreach(PositiveMoment::where('user_id',$user->id)->where('status','approved')->whereBetween('moment_date',[$from,$to])->select('moment_date')->get() as $moment)$add($moment->moment_date->toDateString(),'moment');
  $utcFrom=$start->utc();$utcTo=$end->utc();
  foreach(Comment::where('user_id',$user->id)->where('status','approved')->whereBetween('created_at',[$utcFrom,$utcTo])->select('created_at')->get() as $c)$add($c->created_at->timezone('Europe/Amsterdam')->toDateString(),'comment');
  foreach(Article::where('author_id',$user->id)->where('origin','community')->where('status','published')->whereBetween('published_at',[$utcFrom,$utcTo])->select('published_at')->get() as $a)$add($a->published_at->timezone('Europe/Amsterdam')->toDateString(),'story');
  foreach(DB::table('lightpoint_events')->where('user_id',$user->id)->where('active',true)->whereBetween('period',[$from,$to])->select('period','kind')->selectRaw('SUM(points) AS amount')->groupBy('period','kind')->get() as $row){$days[$row->period]['points']=($days[$row->period]['points']??0)+(int)$row->amount;$days[$row->period]['points_by_kind'][$row->kind]=(int)$row->amount;}
  return ['month'=>$start,'selected'=>CarbonImmutable::parse($selected,'Europe/Amsterdam'),'days'=>$days,'detail'=>$days[$selected]??[],'visits'=>count(array_filter($days,fn($d)=>!empty($d['visited']))),'active_days'=>count(array_filter($days,fn($d)=>!empty($d['activities'])))];
 }
}
