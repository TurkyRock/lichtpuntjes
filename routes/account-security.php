<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountSecurityController as Security;
Route::middleware([\App\Http\Middleware\CommunityLocale::class,\App\Http\Middleware\CookiePrivacy::class,'auth',\App\Http\Middleware\CommunityActive::class])->group(function(){
    Route::get('/beveiliging',[Security::class,'index']);
    Route::post('/profiel/email',[\App\Http\Controllers\AccountEmailController::class,'store'])->middleware('throttle:3,60,account-email-request:');
    Route::post('/profiel/email/annuleren',[\App\Http\Controllers\AccountEmailController::class,'cancel'])->middleware('throttle:6,1,account-email-cancel:');
    Route::get('/profiel/email/bevestigen/{change}',[\App\Http\Controllers\AccountEmailController::class,'show'])->middleware(['signed','throttle:10,1,account-email-view:'])->name('account.email.confirm');
    Route::post('/profiel/email/bevestigen/{change}',[\App\Http\Controllers\AccountEmailController::class,'confirm'])->middleware(['signed','throttle:6,1,account-email-confirm:']);
    Route::post('/profiel/naam',[\App\Http\Controllers\ProfileNameController::class,'update'])->middleware('throttle:6,1');
    Route::get('/beveiliging/bevestigen',[Security::class,'confirmation'])->name('password.confirm');
    Route::post('/beveiliging/bevestigen',[Security::class,'confirm'])->middleware('throttle:6,1');
    Route::post('/beveiliging/wachtwoord',[Security::class,'password'])->middleware('throttle:4,1');
    Route::post('/beveiliging/overal-uitloggen',[Security::class,'logoutEverywhere'])->middleware('throttle:6,1');
});
