<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AccountPrivacyController,NewsPreferencesController,ContentReportController,DetachedSupportController};
Route::middleware([\App\Http\Middleware\CommunityLocale::class,\App\Http\Middleware\CookiePrivacy::class])->group(function(){
 Route::get('/cookies',fn()=>view('community.cookies'));
 Route::get('/contact',fn()=>view('community.contact'));
 Route::get('/steun/beheren',[DetachedSupportController::class,'requestForm']);
 Route::post('/steun/beheren',[DetachedSupportController::class,'requestLink'])->middleware('throttle:3,60,detached-email:');
 Route::get('/steun/beheren/{pledge}',[DetachedSupportController::class,'show'])->middleware(['signed','throttle:20,1,detached-view:'])->name('support.detached');
 Route::post('/steun/beheren/{pledge}',[DetachedSupportController::class,'stop'])->middleware(['signed','throttle:6,1,detached-stop:']);
 Route::get('/melden/{kind}/{id}',[ContentReportController::class,'form'])->whereIn('kind',['article','comment','moment'])->whereNumber('id');
 Route::middleware(['auth',\App\Http\Middleware\CommunityActive::class])->group(function(){
  Route::post('/profiel/nieuwsvoorkeuren',[NewsPreferencesController::class,'update'])->middleware('throttle:10,1,preferences:');
  Route::delete('/profiel/bijdragen/{kind}/{id}',[AccountPrivacyController::class,'eraseContribution'])->whereIn('kind',['comment','story'])->whereNumber('id')->middleware('throttle:10,1,erase-contribution:');
  Route::post('/profiel/export',[AccountPrivacyController::class,'export'])->middleware('throttle:3,60,privacy-export:');
  Route::delete('/profiel/verwijderen',[AccountPrivacyController::class,'destroy'])->middleware('throttle:3,60,privacy-delete:');
  Route::post('/melden/{kind}/{id}',[ContentReportController::class,'store'])->whereIn('kind',['article','comment','moment'])->whereNumber('id')->middleware(['verified','throttle:3,60,content-report:']);
  Route::get('/beheer/meldingen',[ContentReportController::class,'index'])->middleware('verified');
  Route::post('/beheer/meldingen/{id}',[ContentReportController::class,'resolve'])->whereNumber('id')->middleware(['verified','throttle:30,1,report-resolve:']);
  Route::delete('/mijn-momentjes/prullenbak/{id}',function(\Illuminate\Http\Request $r,int $id){$m=\App\Models\PositiveMoment::withTrashed()->where('user_id',$r->user()->id)->findOrFail($id);\Illuminate\Support\Facades\DB::table('contribution_consents')->where('kind','moment')->where('target_id',$id)->delete();\App\Models\LocalAiJob::query()->delete();\App\Models\LaptopImageJob::where('target_type','moment')->where('target_id',$id)->delete();$m->forceDelete();return back()->with('status','Momentje definitief verwijderd.');})->whereNumber('id')->middleware('throttle:10,1,erase-moment:');
 });
});
