<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class LocalAiWorkerToken {
 public function handle(Request $r,Closure $next){
  abort_unless(config('local-ai.enabled'),404);abort_unless($r->isSecure()||app()->environment('testing'),403);
  $hash=(string)config('local-ai.token_hash');$token=(string)$r->bearerToken();
  abort_unless(preg_match('/^[a-f0-9]{64}$/D',$hash)&&strlen($token)>=32&&strlen($token)<=256&&hash_equals($hash,hash('sha256',$token)),401);
  abort_if(strlen($r->getContent())>300000,413);
  return $next($r)->header('Cache-Control','private, no-store');
 }
}
