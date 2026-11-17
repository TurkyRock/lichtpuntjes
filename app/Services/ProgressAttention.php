<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\{DB,Schema};
class ProgressAttention {
 private function ready(User $u):bool {return $u->hasVerifiedEmail()&&!$u->is_suspended&&Schema::hasTable('lightpoint_progress_views');}
 private function ensure(User $u):void {DB::table('lightpoint_progress_views')->insertOrIgnore(['user_id'=>$u->id,'account_seen_event_id'=>0,'badges_seen_event_id'=>0,'badges_seen_points'=>0,'created_at'=>now(),'updated_at'=>now()]);}
 public function snapshot(User $u):array {
  $summary=app(Lightpoints::class)->summary($u);
  $empty=['event_id'=>0,'account_new_points'=>0,'badge_new_points'=>0,'previous_total'=>$summary['points'],'total'=>$summary['points'],'new_badges'=>[]];
  if(!$this->ready($u))return $empty;
  $this->ensure($u);$seen=DB::table('lightpoint_progress_views')->where('user_id',$u->id)->first();
  $events=DB::table('lightpoint_events')->where('user_id',$u->id)->where('active',true)->where('points','>',0);
  $last=(int)(clone $events)->max('id');
  $account=(int)(clone $events)->where('id','>',$seen->account_seen_event_id)->sum('points');
  $badges=(int)(clone $events)->where('id','>',$seen->badges_seen_event_id)->sum('points');
  $earned=$badges>0?array_values(array_filter($summary['stages'],fn($b)=>$b['points']>max(0,$seen->badges_seen_points)&&$b['points']<=$summary['points'])):[];
  return ['event_id'=>$last,'account_new_points'=>$account,'badge_new_points'=>$badges,
   'previous_total'=>min($summary['points'],max(0,(int)$seen->badges_seen_points)),'total'=>$summary['points'],
   'new_badges'=>array_column($earned,'id')];
 }
 public function acknowledge(User $u,string $section,int $observed):array {
  if(!$this->ready($u))return $this->snapshot($u);
  $this->ensure($u);
  DB::transaction(function()use($u,$section,$observed){
   $seen=DB::table('lightpoint_progress_views')->where('user_id',$u->id)->lockForUpdate()->first();
   // The browser can acknowledge only its own, already observed events.
   $events=DB::table('lightpoint_events')->where('user_id',$u->id)->where('id','<=',$observed)->where('active',true);
   $last=(int)(clone $events)->max('id');
   $update=['account_seen_event_id'=>max((int)$seen->account_seen_event_id,$last),'updated_at'=>now()];
   if($section==='badges'&&$last>=(int)$seen->badges_seen_event_id){
    $update['badges_seen_event_id']=$last;$update['badges_seen_points']=max(0,(int)(clone $events)->sum('points'));
   }
   DB::table('lightpoint_progress_views')->where('user_id',$u->id)->update($update);
  },3);
  return $this->snapshot($u);
 }
}
