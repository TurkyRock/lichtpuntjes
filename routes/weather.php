<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::middleware([\App\Http\Middleware\CommunityLocale::class, \App\Http\Middleware\CookiePrivacy::class])->group(function () {
    Route::get('/weer', [WeatherController::class, 'index'])->name('weather');
    Route::get('/weer/data', [WeatherController::class, 'data'])->middleware('throttle:120,1');
});
