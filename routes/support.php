<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupportController as Support;
Route::middleware([\App\Http\Middleware\CommunityLocale::class,\App\Http\Middleware\CookiePrivacy::class])->group(function(){
 Route::get('/steun',[Support::class,'index']);
 Route::post('/steun/uitnodiging/getoond',[\App\Http\Controllers\SupportInvitationController::class,'shown'])->middleware('throttle:20,1');
 Route::post('/steun/uitnodiging/sluiten',[\App\Http\Controllers\SupportInvitationController::class,'dismiss'])->middleware('throttle:20,1');
 Route::post('/steun/betalen',[Support::class,'pay'])->middleware('throttle:6,1');
 Route::get('/steun/bedankt/{id}',[Support::class,'thanks'])->middleware('throttle:20,1');
 Route::middleware(['auth',\App\Http\Middleware\CommunityActive::class])->group(function(){
  Route::get('/mijn-bijdragen',[Support::class,'mine']);
  Route::post('/mijn-bijdragen/{pledge}/stop',[Support::class,'stop'])->middleware('throttle:6,1');
  Route::get('/beheer/bijdragen',[Support::class,'admin']);
 });
});
// Only this provider callback bypasses CSRF. Its body is never trusted; the
// payment, amount, mode, customer and subscription are fetched and verified.
Route::post('/steun/mollie/webhook',[Support::class,'webhook'])
 ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])->middleware('throttle:120,1');
