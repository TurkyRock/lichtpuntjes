<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class RecentAdminConfirmation {
    public function handle(Request $request, Closure $next) {
        if (!$request->is('beheer','beheer/*') || !$request->user()) return $next($request);
        abort_unless($request->user()->is_admin,403);
        abort_if($request->user()->is_suspended,403,'Dit account is gepauzeerd.');
        if (!$request->user()->hasVerifiedEmail()) return redirect('/email/verify');
        $confirmed=(int)$request->session()->get('auth.password_confirmed_at',0);
        if ($confirmed < now()->timestamp-(int)config('account-security.admin_confirmation_minutes',30)*60) {
            if ($request->expectsJson()) return response()->json(['message'=>'Bevestig eerst je wachtwoord.','confirmation_url'=>'/beveiliging/bevestigen'],423);
            if ($request->isMethod('GET')) $request->session()->put('url.intended',$request->getRequestUri());
            return redirect('/beveiliging/bevestigen')->with('status','Bevestig je wachtwoord om verder te gaan in beheer.');
        }
        return $next($request);
    }
}
