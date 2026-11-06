<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB,Hash};
use Illuminate\Support\Str;
class AccountSecurityController extends Controller {
    public function index(Request $r) {
        return redirect('/privacy/verzoeken?lang='.app()->getLocale().'#account-security');
    }
    public function confirmation() { return view('community.confirm-password'); }
    public function confirm(Request $r) {
        $r->validate(['password'=>'required|string|max:128']);
        if (!Hash::check($r->password,$r->user()->password)) return back()->withErrors(['password'=>'Dit wachtwoord klopt niet.']);
        $r->session()->put('auth.password_confirmed_at',now()->timestamp);
        return redirect()->intended('/privacy/verzoeken?lang='.app()->getLocale().'#account-security');
    }
    public function password(Request $r) {
        $r->validate(['current_password'=>'required|current_password','password'=>\App\Support\AccountPasswords::rules()]);
        $r->user()->forceFill(['password'=>$r->password])->save();
        return $this->endSession($r,'Je wachtwoord is gewijzigd. Log opnieuw in.');
    }
    public function logoutEverywhere(Request $r) {
        $r->validate(['confirm'=>'accepted']);
        DB::transaction(function()use($r){
            $u=\App\Models\User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $u->forceFill(['security_version'=>(int)$u->security_version+1,'remember_token'=>Str::random(60)])->save();
            app(\App\Services\AccountSecurity::class)->record($u,'logged_out_everywhere');
            if(config('session.driver')==='database')DB::table(config('session.table','sessions'))->where('user_id',$u->id)->delete();
        });
        return $this->endSession($r,'Je bent op alle apparaten uitgelogd.');
    }
    private function endSession(Request $r,string $message) {
        Auth::guard()->logoutCurrentDevice();$r->session()->invalidate();$r->session()->regenerateToken();
        return redirect('/inloggen')->with('status',$message);
    }
}
