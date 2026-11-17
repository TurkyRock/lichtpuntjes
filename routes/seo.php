<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicStoryController;
Route::get('/over-ons',[PublicStoryController::class,'information'])->defaults('locale','nl')->defaults('page','about');
Route::get('/en/about',[PublicStoryController::class,'information'])->defaults('locale','en')->defaults('page','about');
Route::get('/werkwijze',[PublicStoryController::class,'information'])->defaults('locale','nl')->defaults('page','approach');
Route::get('/en/how-we-work',[PublicStoryController::class,'information'])->defaults('locale','en')->defaults('page','approach');
