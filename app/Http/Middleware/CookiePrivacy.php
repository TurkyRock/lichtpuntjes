<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use App\Support\CookieChoice;
class CookiePrivacy {
 public function handle(Request $r,Closure $next){
  if(!CookieChoice::allowed($r)&&$r->hasCookie('lichtpunt_visitor'))Cookie::queue(Cookie::forget('lichtpunt_visitor','/'));
  $response=$next($r);
  if(!CookieChoice::allowed($r))foreach(array_keys($r->cookies->all()) as $name){
   if(preg_match('/^_ga(?:_[A-Za-z0-9_]+)?$/D',$name))$response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie($name,'',1,'/',null,$r->isSecure(),false,false,'lax'));
  }
  if(str_contains((string)$response->headers->get('Content-Type'),'text/html'))$response->headers->set('Cache-Control','private, no-store');
  return $response;
 }
}
