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

Route::prefix('local-ai')->middleware([\App\Http\Middleware\LocalAiWorkerToken::class,'throttle:120,1,local-ai-worker:'])->group(function(){
 Route::post('/health',fn()=>response()->json(['ready'=>true,'model'=>config('local-ai.model'),'vision_model'=>config('local-ai.vision_model')]));
 Route::post('/claim',[\App\Http\Controllers\LocalAiWorkerController::class,'claim']);
 Route::post('/{id}/heartbeat',[\App\Http\Controllers\LocalAiWorkerController::class,'heartbeat'])->whereNumber('id');
 Route::post('/{id}/complete',[\App\Http\Controllers\LocalAiWorkerController::class,'complete'])->whereNumber('id');
 Route::post('/{id}/fail',[\App\Http\Controllers\LocalAiWorkerController::class,'fail'])->whereNumber('id');
});
