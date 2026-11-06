<?php
namespace App\Http\Middleware;
use App\Services\BetaTests;
use Closure;
use Illuminate\Http\Request;
class BetaGate {
 public function handle(Request $r,Closure $next){
  if(!config('beta.enabled')||!config('beta.closed_site'))return $next($r);
  if($r->is('robots.txt'))return response("User-agent: *\nDisallow: /\n",200,['Content-Type'=>'text/plain','X-Robots-Tag'=>'noindex, nofollow']);
  if($r->is('testgroep','testgroep/*','inloggen','uitloggen','wachtwoord-vergeten','reset-password/*','beveiliging/*')||$r->user()?->is_admin||app(BetaTests::class)->participant($r))return $next($r);
  if($r->expectsJson())return response()->json(['message'=>'Open eerst je persoonlijke testlink.'],403);
  return redirect('/testgroep/toegang');
 }
}
