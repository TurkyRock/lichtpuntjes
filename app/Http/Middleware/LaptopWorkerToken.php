<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class LaptopWorkerToken {
 public function handle(Request $r,Closure $next){
  abort_unless(config('laptop-images.enabled'),404);
  $expected=(string)config('laptop-images.token_hash');$token=(string)$r->bearerToken();
  abort_unless($r->isSecure()||app()->environment('testing'),403);
  abort_unless(preg_match('/^[a-f0-9]{64}$/D',$expected)&&strlen($token)>=32&&strlen($token)<=256&&hash_equals($expected,hash('sha256',$token)),401);
  return $next($r)->header('Cache-Control','private, no-store');
 }
}
