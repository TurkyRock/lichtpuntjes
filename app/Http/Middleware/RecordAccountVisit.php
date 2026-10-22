<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Services\ActivityCalendar;
class RecordAccountVisit {
 public function handle(Request $request,Closure $next){
  $response=$next($request);
  if($request->isMethod('GET')&&!$request->expectsJson()&&$response->getStatusCode()===200&&str_contains((string)$response->headers->get('Content-Type'),'text/html')&&$request->user()&&!$request->is('auth/*','inloggen','aanmelden','email/*','reset-password/*','wachtwoord-vergeten'))app(ActivityCalendar::class)->visit($request->user());
  return $response;
 }
}
