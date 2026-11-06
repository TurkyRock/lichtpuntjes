<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BetaTestController as Beta;
Route::get('/testgroep/toegang',[Beta::class,'access']);
Route::post('/testgroep/toegang',[Beta::class,'enter'])->middleware('throttle:6,1,beta-entry:');
Route::get('/testgroep',[Beta::class,'index']);
Route::get('/testgroep/sessie',[Beta::class,'sessionInfo'])->middleware('throttle:60,1');
Route::put('/testgroep/antwoord/{task}',[Beta::class,'save'])->middleware('throttle:60,1,beta-save:');
Route::post('/testgroep/afronden',[Beta::class,'complete'])->middleware('throttle:10,1');
Route::middleware('auth')->group(function(){
 Route::get('/beheer/testgroep',[Beta::class,'overview']);
 Route::get('/beheer/testgroep/export',[Beta::class,'export']);
 Route::post('/beheer/testgroep/uitnodigen',[Beta::class,'invite'])->middleware('throttle:6,1');
 Route::post('/beheer/testgroep/{person}/nieuwe-link',[Beta::class,'reissue'])->whereUuid('person')->middleware('throttle:10,1');
 Route::post('/beheer/testgroep/{person}/intrekken',[Beta::class,'revoke'])->whereUuid('person')->middleware('throttle:10,1');
});
