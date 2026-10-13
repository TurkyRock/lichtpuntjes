<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class CommunityActive {
 public function handle(Request $r,Closure $next){abort_if($r->user()?->is_suspended,403,'Dit account is gepauzeerd.');return $next($r);}
}
