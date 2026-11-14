<?php
namespace App\Http\Controllers;
use App\Services\Maintenance\{Retention,StorageUsage};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
class StorageMaintenanceController extends Controller {
 private function owner(Request $r):void {abort_unless($r->user()?->is_admin,403);}
 public function index(Request $r,StorageUsage $usage){$this->owner($r);return view('community.storage-maintenance',['usage'=>$usage->report(),'last'=>Cache::get('site-retention-last'),'preview'=>Cache::get('site-retention-preview'),'backup'=>Cache::get('site-backup-last')]);}
 public function run(Request $r,Retention $retention,StorageUsage $usage){
  $this->owner($r);$v=$r->validate(['action'=>'required|in:preview,apply']);
  try{$retention->run($v['action']==='apply');$usage->report(true);}catch(\Throwable $e){report($e);return back()->withErrors(['maintenance'=>'Onderhoud kon niet afronden. Een andere verwerking kan nog actief zijn; probeer later opnieuw.']);}
  return back()->with('status',$v['action']==='apply'?'Onderhoud uitgevoerd volgens de bewaartermijnen.':'Proefcontrole klaar. Er is niets verwijderd.');
 }
}
