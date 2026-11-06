<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class PointsDiary
{
 public const KINDS=['heart','read','return','comment','moment','share','invite'];
 public function forUser(User $user):array {
  $base=DB::table('lightpoint_events')->where('user_id',$user->id);
  $days=(clone $base)->select('period')->groupBy('period')->orderByDesc('period')->paginate(3)->appends(['section'=>'points','lang'=>app()->getLocale()]);
  $periods=$days->getCollection()->pluck('period');
  $records=$periods->isEmpty()?collect():(clone $base)->whereIn('period',$periods)->select('period','kind','active','label')->selectRaw('COUNT(*) as event_count, SUM(points) as point_sum')->groupBy('period','kind','active','label')->get()->groupBy('period');
  $days->setCollection($days->getCollection()->map(function($day)use($records){
   $groups=[];
   foreach($records->get($day->period,collect()) as $record){
    $key=json_encode([$record->kind,(bool)$record->active,in_array($record->kind,self::KINDS,true)?null:$record->label]);
    if(!isset($groups[$key]))$groups[$key]=['kind'=>$record->kind,'active'=>(bool)$record->active,'label'=>$record->label,'count'=>0,'points'=>0];
    $groups[$key]['count']+=(int)$record->event_count;$groups[$key]['points']+=(int)$record->point_sum;
   }
   $groups=array_values($groups);
   usort($groups,fn($a,$b)=>((int)$b['active']<=>(int)$a['active'])?:((array_search($a['kind'],self::KINDS,true)===false?99:array_search($a['kind'],self::KINDS,true))<=>(array_search($b['kind'],self::KINDS,true)===false?99:array_search($b['kind'],self::KINDS,true)))?:strcmp($a['label'],$b['label']));
   return ['period'=>$day->period,'groups'=>$groups,'points'=>array_sum(array_map(fn($g)=>$g['active']?$g['points']:0,$groups))];
  }));
  return ['days'=>$days,'today'=>(int)(clone $base)->where('active',true)->where('period',now('Europe/Amsterdam')->toDateString())->sum('points')];
 }
 public static function label(array $group,bool $en):string {
  $n=$group['count'];$one=$n===1;
  return match($group['kind']){
   'heart'=>$en?$n.' '.($one?'heart given':'hearts given'):$n.' '.($one?'hartje gegeven':'hartjes gegeven'),
   'read'=>$en?$n.' '.($one?'article read':'articles read'):$n.' '.($one?'artikel gelezen':'artikelen gelezen'),
   'return'=>$en?($one?'Stopped by':'Stopped by '.$n.' times'):($one?'Even langsgekomen':$n.' keer langsgekomen'),
   'comment'=>$en?$n.' '.($one?'friendly comment':'friendly comments'):$n.' vriendelijke '.($one?'reactie':'reacties'),
   'moment'=>$en?$n.' '.($one?'little joy saved':'little joys saved'):$n.' '.($one?'momentje bewaard':'momentjes bewaard'),
   'share'=>$en?$n.' '.($one?'bright spot ready to share':'bright spots ready to share'):$n.' '.($one?'lichtpuntje klaargezet om te delen':'lichtpuntjes klaargezet om te delen'),
   'invite'=>$en?$n.' '.($one?'friend brought a first smile':'friends brought a first smile'):$n.' '.($one?'vriend bracht een eerste glimlach':'vrienden brachten een eerste glimlach'),
   default=>($n>1?$n.' × ':'').$group['label'],
  };
 }
 public static function signed(int $points):string {return $points>0?'+'.$points:(string)$points;}
}
