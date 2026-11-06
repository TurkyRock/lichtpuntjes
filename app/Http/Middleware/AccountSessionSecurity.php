<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AccountSessionSecurity {
    public function handle(Request $request, Closure $next) {
        if ($user=$request->user()) {
            $version = (int)$user->newQuery()->whereKey($user->id)->value('security_version');
            if ((int)$request->session()->get('account_security_version', 0) !== $version) {
                // Do not rotate the remember token here: another valid session may be using it.
                Auth::guard()->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return $request->expectsJson() ? response()->json(['message'=>'Log opnieuw in.'],401) : redirect('/inloggen')->with('status','Log opnieuw in om verder te gaan.');
            }
        }
        return $next($request);
    }
}
