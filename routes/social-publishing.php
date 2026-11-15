<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialPublishingController as Social;
Route::middleware(['auth','verified',\App\Http\Middleware\CommunityActive::class,\App\Http\Middleware\CommunityLocale::class])->prefix('beheer/sociaal')->group(function(){
 Route::get('/',[Social::class,'index']);
 Route::get('/beeld',[Social::class,'image'])->middleware('throttle:120,1,social-image:');
 Route::post('/voorbereiden/{kind}',[Social::class,'prepare'])->whereIn('kind',['daily','reads','likes'])->middleware('throttle:12,1,social-prepare:');
 Route::post('/{publication}/tekst',[Social::class,'caption'])->whereNumber('publication')->middleware('throttle:30,1,social-caption:');
 Route::post('/{publication}/postlink',[Social::class,'register'])->whereNumber('publication')->middleware('throttle:20,1,social-register:');
 Route::post('/kanaal/{delivery}/gecontroleerd',[Social::class,'checked'])->whereNumber('delivery')->middleware('throttle:30,1,social-check:');
});
