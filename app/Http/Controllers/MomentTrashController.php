<?php
namespace App\Http\Controllers;
use App\Models\PositiveMoment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MomentTrashController extends Controller {
 public function index(Request $r){return view('community.moment-trash',['moments'=>PositiveMoment::onlyTrashed()->where('user_id',$r->user()->id)->where('deleted_at','>=',now()->subDays(config('retention.trash_days')))->latest('deleted_at')->paginate(15)]);}
 public function restore(Request $r,int $id){
  $m=DB::transaction(function()use($r,$id){$m=PositiveMoment::onlyTrashed()->where('user_id',$r->user()->id)->where('deleted_at','>=',now()->subDays(config('retention.trash_days')))->whereKey($id)->lockForUpdate()->firstOrFail();
   $m->visibility='private';$m->restore();DB::table('moment_hearts')->where('moment_id',$m->id)->delete();return $m;});
  app(\App\Services\MomentModerator::class)->syncPoints($m);
  if($m->status==='pending')\App\Jobs\ReviewMoment::dispatch($m->id,$m->revision);
  return redirect('/mijn-momentjes?month='.$m->moment_date->format('Y-m'))->with('status','Je momentje is teruggezet als privéherinnering.');
 }
}
