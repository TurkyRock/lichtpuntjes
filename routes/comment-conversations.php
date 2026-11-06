<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommentConversationController;
Route::middleware([\App\Http\Middleware\CommunityLocale::class,\App\Http\Middleware\CookiePrivacy::class,'auth',\App\Http\Middleware\CommunityActive::class,'verified'])->group(function(){
 Route::post('/comments/{comment}/heart',[CommentConversationController::class,'heart'])->whereNumber('comment')->middleware('throttle:40,1,comment-hearts:');
 Route::post('/comments/{comment}/reply',[CommentConversationController::class,'reply'])->whereNumber('comment')->middleware('throttle:4,1,comment-reply:');
});
