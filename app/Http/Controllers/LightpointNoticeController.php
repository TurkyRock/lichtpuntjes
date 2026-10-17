<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\{Lightpoints,BadgeCatalog};
class LightpointNoticeController extends Controller {
 public function collect(Request $request){
  $notice=DB::transaction(function()use($request){
   $user=User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
   abort_if($user->is_suspended,403);
   $query=DB::table('lightpoint_events')->where('user_id',$user->id)->where('id','>',(int)$user->lightpoint_notice_seen_id);
   $last=(int)(clone $query)->max('id');
   if(!$last)return null;
   $active=(clone $query)->where('active',true);
   $points=(int)(clone $active)->sum('points');$count=(clone $active)->count();$event=(clone $active)->orderByDesc('id')->first();
   $user->forceFill(['lightpoint_notice_seen_id'=>$last])->save();
   if($points<=0)return null;
   $summary=app(Lightpoints::class)->summary($user);$before=$summary['points']-$points;
   $earned=array_values(array_filter(BadgeCatalog::stages(),fn($b)=>$b['points']>$before&&$b['points']<=$summary['points']&&$b['points']>0));
   $en=app()->getLocale()==='en';
   $labels=['read'=>$en?'Your daily dose of good news':'Je dagelijkse dosis goed nieuws','share'=>$en?'A bright spot ready to share':'Een lichtpuntje klaargezet om te delen','comment'=>$en?'A friendly comment':'Een vriendelijke reactie','invite'=>$en?'Your friend brought a first smile':'Een vriend bracht een eerste glimlach'];
   return ['points'=>$points,'total'=>$summary['points'],'label'=>$count===1?($labels[$event->kind]??($en?'A little more sunshine':'Een beetje meer zon')):($en?'Your latest bright moments':'Je nieuwste lichtpuntjes'),'badge'=>$earned?end($earned):null];
  },3);
  return response()->json(['notice'=>$notice])->header('Cache-Control','private, no-store');
 }
}
