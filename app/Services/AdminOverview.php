<?php
namespace App\Services;
use App\Models\{Article,Comment,LightspotSubmission,PositiveMoment,ProfilePhoto,User};
use Illuminate\Support\Facades\{DB,Schema};
class AdminOverview {
 public function counts():array {
  return ['comments'=>Comment::whereIn('status',['pending','doubt'])->count(),
   'stories'=>LightspotSubmission::whereIn('status',['pending','doubt','image_wait'])->count(),
   'reports'=>DB::table('lightspot_reports')->where('resolved',false)->count(),
   'moments'=>PositiveMoment::where('visibility','public')->whereIn('status',['pending','doubt'])->count(),
   'photos'=>ProfilePhoto::whereIn('status',['pending','doubt'])->whereNotNull('path')->count(),
   'requests'=>DB::table('privacy_requests')->whereNull('resolved_at')->count(),
   'users'=>User::count(),'articles'=>Article::visible()->count()];
 }
 public function system():array {
  return ['queued'=>Schema::hasTable('jobs')?DB::table('jobs')->count():null,
   'failed'=>Schema::hasTable('failed_jobs')?DB::table('failed_jobs')->count():null,
   'queue'=>config('queue.default'),'mailConfigured'=>!in_array(config('mail.default'),['log','array',null],true),
   'paymentMode'=>app(SupportPayments::class)->mode(),
   'newsErrors'=>Article::where('status','error')->count(),
   'imageWaiting'=>LightspotSubmission::where('status','image_wait')->count()];
 }
}
