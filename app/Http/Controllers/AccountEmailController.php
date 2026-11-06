<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Notifications\ConfirmAccountEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB,Notification,URL};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class AccountEmailController extends Controller {
 private function text(string $nl,string $en):string {return app()->getLocale()==='en'?$en:$nl;}
 public function store(Request $r) {
  $r->merge(['email'=>Str::lower(trim((string)$r->input('email'))),'email_confirmation'=>Str::lower(trim((string)$r->input('email_confirmation')))]);
  $v=$r->validate(['email'=>'required|email|max:254|confirmed|unique:users,email','email_password'=>'required|string|max:128|current_password']);
  if(User::whereRaw('LOWER(email) = ?',[$v['email']])->exists())throw ValidationException::withMessages(['email'=>$this->text('Dit e-mailadres is al in gebruik.','This email is already in use.')]);
  $id=(string)Str::uuid();$expires=now()->addHour();
  DB::transaction(function()use($r,$v,$id,$expires){
   $u=User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
   // Check again under the lock: another request may have changed the password.
   if(!\Illuminate\Support\Facades\Hash::check($v['email_password'],$u->password))throw ValidationException::withMessages(['email_password'=>$this->text('Controleer je huidige wachtwoord.','Check your current password.')]);
   DB::table('account_email_changes')->updateOrInsert(['user_id'=>$u->id],['id'=>$id,'email'=>$v['email'],'security_version'=>(int)$u->security_version,'expires_at'=>$expires]);
  });
  $url=URL::temporarySignedRoute('account.email.confirm',$expires,['change'=>$id,'lang'=>app()->getLocale()]);
  try {Notification::route('mail',$v['email'])->notify(new ConfirmAccountEmail($url,app()->getLocale()));}
  catch(\Throwable $e){DB::table('account_email_changes')->where('id',$id)->delete();return back()->withErrors(['email'=>$this->text('De bevestigingsmail kon niet worden verstuurd. Je huidige adres blijft behouden. Probeer het later opnieuw.','The confirmation email could not be sent. Your current address is unchanged. Try again later.')]);}
  return redirect('/privacy/verzoeken?lang='.app()->getLocale().'#account-email')->with('status',$this->text('Controleer je nieuwe mailbox. Pas na je bevestiging wijzigen we je e-mailadres.','Check your new inbox. Your email only changes after confirmation.'));
 }
 private function pending(Request $r,string $change,bool $lock=false) {
  $q=DB::table('account_email_changes')->where('id',$change)->where('user_id',$r->user()->id)->where('expires_at','>',now());
  $p=($lock?$q->lockForUpdate():$q)->first();
  abort_unless($p && (int)$p->security_version===(int)$r->user()->fresh()->security_version,410,$this->text('Deze aanvraag is verlopen. Vraag een nieuwe bevestigingsmail aan bij Mijn gegevens.','This request expired. Request a new email from My details.'));
  return $p;
 }
 public function show(Request $r,string $change) {return view('community.confirm-email-change',['pending'=>$this->pending($r,$change),'confirmUrl'=>$r->fullUrl()]);}
 public function confirm(Request $r,string $change) {
  $r->validate(['confirm'=>'accepted']);
  try {DB::transaction(function()use($r,$change){
   $u=User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
   $p=$this->pending($r,$change,true);
   if(User::whereRaw('LOWER(email) = ?',[$p->email])->exists())throw ValidationException::withMessages(['email'=>$this->text('Dit e-mailadres is inmiddels in gebruik. Kies een ander adres bij Mijn gegevens.','This email is now in use. Choose another address in My details.')]);
   $u->forceFill(['email'=>$p->email])->save();
   // The address was just verified via the signed link. Keep the observer's session revocation.
   $u->forceFill(['email_verified_at'=>now()])->save();
   DB::table('account_email_changes')->where('id',$change)->delete();
  });} catch(\Illuminate\Database\UniqueConstraintViolationException $e) {
   throw ValidationException::withMessages(['email'=>$this->text('Dit e-mailadres is inmiddels in gebruik. Kies een ander adres bij Mijn gegevens.','This email is now in use. Choose another address in My details.')]);
  }
  Auth::guard()->logoutCurrentDevice();$r->session()->invalidate();$r->session()->regenerateToken();
  return redirect('/inloggen?lang='.app()->getLocale())->with('status',$this->text('Je e-mailadres is gewijzigd en bevestigd. Log opnieuw in met je nieuwe adres.','Your email has been changed and verified. Sign in again with your new address.'));
 }
 public function cancel(Request $r) {DB::table('account_email_changes')->where('user_id',$r->user()->id)->delete();return back()->with('status',$this->text('De wijziging is geannuleerd. Je huidige e-mailadres blijft behouden.','The change was cancelled. Your current email is unchanged.'));}
}
