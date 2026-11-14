<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LaptopImageController;
Route::prefix('laptop-images')->middleware([\App\Http\Middleware\LaptopWorkerToken::class,'throttle:120,1,laptop-worker:'])->group(function(){
 Route::post('/health',fn()=>response()->json(['ready'=>true]));
 Route::post('/claim',[LaptopImageController::class,'claim']);
 Route::post('/{id}/heartbeat',[LaptopImageController::class,'heartbeat'])->whereNumber('id');
 Route::post('/{id}/complete',[LaptopImageController::class,'complete'])->whereNumber('id');
 Route::post('/{id}/fail',[LaptopImageController::class,'fail'])->whereNumber('id');
});
