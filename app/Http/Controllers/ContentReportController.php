<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ContentReportController extends Controller {
 private function target(string $kind,int $id){return match($kind){'article'=>\App\Models\Article::visible()->findOrFail($id),'comment'=>\App\Models\Comment::visible()->whereHas('article',fn($q)=>$q->visible())->findOrFail($id),'moment'=>\App\Models\PositiveMoment::publicVisible()->findOrFail($id),default=>abort(404)};}
 public function form(string $kind,int $id){$target=$this->target($kind,$id);return view('community.report',compact('kind','id'));}
 public function store(Request $r,string $kind,int $id){$this->target($kind,$id);$v=$r->validate(['reason'=>'required|in:privacy,harm,discrimination,misleading,spam,other','message'=>'nullable|string|max:1000','website'=>'prohibited']);DB::table('content_reports')->insertOrIgnore(['user_id'=>$r->user()->id,'kind'=>$kind,'target_id'=>$id,'reason'=>$v['reason'],'message'=>$v['message']??null,'created_at'=>now(),'updated_at'=>now()]);return back()->with('status',app()->getLocale()==='en'?'Report received privately. The administrator will review it.':'Melding privé ontvangen. De beheerder bekijkt deze.');}
 public function index(Request $r){abort_unless($r->user()->is_admin,403);return view('community.reports-admin',['reports'=>DB::table('content_reports')->orderByRaw('resolved_at IS NOT NULL')->latest('id')->paginate(25)]);}
 public function resolve(Request $r,int $id){abort_unless($r->user()->is_admin,403);$r->validate(['handled'=>'accepted']);abort_unless(DB::table('content_reports')->where('id',$id)->exists(),404);DB::table('content_reports')->where('id',$id)->update(['resolved_at'=>now(),'updated_at'=>now()]);return back()->with('status','Melding afgehandeld.');}
}
