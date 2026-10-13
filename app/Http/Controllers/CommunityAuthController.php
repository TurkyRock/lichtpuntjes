<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
class CommunityAuthController extends Controller {
 public function register(Request $r){
  $r->merge(['email'=>strtolower(trim((string)$r->input('email')))]);
  $v=$r->validate(['name'=>['required','string','min:2','max:40','regex:/^[\pL\pN _.-]+$/u'],'email'=>'required|email|max:254|unique:users,email','password'=>['required','confirmed','max:128',PasswordRule::min(12)->mixedCase()->numbers()],'intentions'=>'accepted','website'=>'prohibited','invite'=>'nullable|string|size:32']);
  $u=User::create(['name'=>trim($v['name']),'email'=>strtolower($v['email']),'password'=>$v['password']]);
  $u->forceFill(['community_accepted_at'=>now(),'community_version'=>'2026-10-10','invite_code'=>bin2hex(random_bytes(16)),'referred_by'=>isset($v['invite'])?User::where('invite_code',$v['invite'])->where('is_suspended',false)->value('id'):null])->save();
  Auth::login($u);$r->session()->regenerate();
  try {event(new Registered($u));$message='Welkom! Bevestig je e-mailadres en breng je eerste glimlach.';}catch(\Throwable $e){$message='Je account is aangemaakt. De bevestigingsmail kon nog niet worden verstuurd; probeer dit straks opnieuw.';}
  return redirect('/profiel')->with('status',$message);
 }
 public function login(Request $r){
  $v=$r->validate(['email'=>'required|email','password'=>'required|string|max:128']);
  $key='login-account:'.hash_hmac('sha256',strtolower(trim($v['email'])),(string)config('app.key'));
  if(\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key,10))return back()->withErrors(['email'=>'Te veel inlogpogingen. Probeer over een kwartier opnieuw.'])->onlyInput('email');
  \Illuminate\Support\Facades\RateLimiter::hit($key,900);
  if(!Auth::attempt(['email'=>strtolower($v['email']),'password'=>$v['password'],'is_suspended'=>false],$r->boolean('remember')))return back()->withErrors(['email'=>'Deze combinatie klopt niet of je account is gepauzeerd.'])->onlyInput('email');
  \Illuminate\Support\Facades\RateLimiter::clear($key);
  $r->session()->regenerate();return redirect()->intended('/profiel');
 }
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/');}
 public function forgot(Request $r){$r->validate(['email'=>'required|email']);try{Password::sendResetLink(['email'=>strtolower($r->email)]);}catch(\Throwable $e){}return back()->with('status','Als dit adres bij ons bekend is, ontvang je een link om je wachtwoord te herstellen.');}
 public function reset(Request $r){
  $v=$r->validate(['token'=>'required','email'=>'required|email','password'=>['required','confirmed','max:128',PasswordRule::min(12)->mixedCase()->numbers()]]);
  $status=Password::reset($v,function(User $u,string $password){$u->forceFill(['password'=>Hash::make($password),'remember_token'=>Str::random(60)])->save();if(config('session.driver')==='database')\Illuminate\Support\Facades\DB::table(config('session.table','sessions'))->where('user_id',$u->id)->delete();event(new PasswordReset($u));});
  return $status===Password::PasswordReset?redirect('/inloggen')->with('status','Wachtwoord vernieuwd. Je kunt weer inloggen.'):back()->withErrors(['email'=>'Deze herstellink is ongeldig of verlopen.']);
 }
 public function resend(Request $r){if(!$r->user()->hasVerifiedEmail())try{$r->user()->sendEmailVerificationNotification();}catch(\Throwable $e){return back()->withErrors(['mail'=>'Bevestigingsmail versturen is nu niet gelukt.']);}return back()->with('status','Controleer je mailbox voor de bevestigingslink.');}
}
