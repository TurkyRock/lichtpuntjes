<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MomentImageController;
Route::middleware([\App\Http\Middleware\CommunityLocale::class, \App\Http\Middleware\CookiePrivacy::class])->group(function () {
    Route::get('/momentjes/{moment}/afbeelding', [MomentImageController::class, 'show'])->whereNumber('moment')->middleware('throttle:180,1');
    Route::post('/momentjes/{moment}/afbeelding', [MomentImageController::class, 'store'])->whereNumber('moment')->middleware(['auth', \App\Http\Middleware\CommunityActive::class, 'verified', 'throttle:5,60,moment-image:']);
});
