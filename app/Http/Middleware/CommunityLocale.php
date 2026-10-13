<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class CommunityLocale {
 public function handle(Request $r,Closure $next){$lang=$r->query('lang');if(in_array($lang,['nl','en'],true))$r->session()->put('lichtpunt_locale',$lang);app()->setLocale($r->session()->get('lichtpunt_locale','nl'));return $next($r);}
}
