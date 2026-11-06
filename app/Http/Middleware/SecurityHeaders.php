<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class SecurityHeaders {
 public function handle(Request $request,Closure $next){
  $nonce=base64_encode(random_bytes(24));$request->attributes->set('csp_nonce',$nonce);
  $response=$next($request);
  if(!$request->isMethod('GET')||$request->is('beveiliging','beveiliging/*','mijn-bijdragen','steun/bedankt/*','news/*','beheer','beheer/*','profiel','mijn-momentjes','profiel/*','profielfotos/*','mijn-lichtpuntjes','mijn-lichtpuntjes/*','auth/*','inloggen','aanmelden','aanmelden/*','lichtpuntje/*','email/*','reset-password/*','wachtwoord-vergeten','privacy/verzoeken')||$response->getStatusCode()>=400)$response->headers->set('X-Robots-Tag','noindex');
  if(config('beta.closed_site')||$request->is('testgroep','testgroep/*')){$response->headers->set('X-Robots-Tag','noindex, nofollow');$response->headers->set('Referrer-Policy','no-referrer');}
  $response->headers->set('X-Content-Type-Options','nosniff');
  $response->headers->set('X-Frame-Options','DENY');
  $response->headers->set('Referrer-Policy',config('beta.closed_site')||$request->is('testgroep','testgroep/*')?'no-referrer':'strict-origin-when-cross-origin');
  $response->headers->set('Permissions-Policy','camera=(), microphone=(), geolocation=()');
  $analytics=\App\Support\Analytics::eligible($request)?' https://www.googletagmanager.com https://*.google-analytics.com https://www.google.com':'';
  $supportForm=$request->is('steun','steun/*')?' https://www.mollie.com https://mollie.com https://checkout.mollie.com':'';
  if($request->is('steun/bedankt/*'))$response->headers->set('Referrer-Policy','no-referrer');
  $response->headers->set('Content-Security-Policy',"default-src 'self'; base-uri 'none'; object-src 'none'; frame-ancestors 'none'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'{$analytics}; form-action 'self'{$supportForm}");
  if(app()->environment('production')&&$request->isSecure())$response->headers->set('Strict-Transport-Security','max-age=31536000');
  if(str_contains((string)$response->headers->get('Content-Type'),'text/html')||$request->user())$response->headers->set('Cache-Control','private, no-store');
  return $response;
 }
}
