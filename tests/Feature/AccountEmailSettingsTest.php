<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Notifications\{ConfirmAccountEmail,AccountSecurityNotice};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http,Notification,URL};
class AccountEmailSettingsTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();\Illuminate\Support\Facades\Cache::flush();Http::preventStrayRequests();Notification::fake();}
 private function member(string $email='old@example.test'):User {return User::forceCreate(['name'=>'Zonnetje','email'=>$email,'password'=>'An existing sunny password','email_verified_at'=>now(),'community_accepted_at'=>now(),'remember_token'=>'old-token']);}
 private function requestChange(User $u,string $email='new@example.test'):string {
  $this->actingAs($u)->from('/privacy/verzoeken')->post('/profiel/email',['email'=>$email,'email_confirmation'=>$email,'email_password'=>'An existing sunny password'])->assertSessionHasNoErrors()->assertRedirect();
  $p=DB::table('account_email_changes')->where('user_id',$u->id)->first();
  return URL::temporarySignedRoute('account.email.confirm',now()->addHour(),['change'=>$p->id,'lang'=>'nl']);
 }
 public function test_confirmation_changes_only_the_address_after_an_explicit_post_and_revokes_sessions():void {
  $u=$this->member();$url=$this->requestChange($u,' NEW@example.test ');
  $this->assertSame('old@example.test',$u->fresh()->email);
  Notification::assertSentOnDemand(ConfirmAccountEmail::class,fn($n,$channels,$to)=>$to->routes['mail']==='new@example.test'&&$n->url===$url);
  $this->get($url)->assertOk()->assertSee('new@example.test');$this->assertSame('old@example.test',$u->fresh()->email);
  $this->post($url,[])->assertSessionHasErrors('confirm');
  $this->post($url,['confirm'=>1,'is_admin'=>1,'name'=>'Ignored'])->assertRedirect('/inloggen?lang=nl');
  $fresh=$u->fresh();$this->assertSame('new@example.test',$fresh->email);$this->assertNotNull($fresh->email_verified_at);$this->assertSame(1,(int)$fresh->security_version);$this->assertNotSame('old-token',$fresh->remember_token);$this->assertFalse((bool)$fresh->is_admin);$this->assertSame('Zonnetje',$fresh->name);$this->assertGuest();$this->assertDatabaseCount('account_email_changes',0);
  Notification::assertSentOnDemand(AccountSecurityNotice::class,fn($n,$channels,$to)=>$to->routes['mail']==='old@example.test'&&$n->kind==='email_changed');
  $this->actingAs($fresh)->withSession(['account_security_version'=>0])->get('/profiel')->assertRedirect('/inloggen');
  $this->post('/inloggen',['email'=>'old@example.test','password'=>'An existing sunny password'])->assertSessionHasErrors();
  $this->post('/inloggen',['email'=>'new@example.test','password'=>'An existing sunny password'])->assertRedirect('/profiel');
  $this->get($url)->assertStatus(410);
 }
 public function test_wrong_password_invalid_and_duplicate_addresses_are_rejected_without_flashing_password():void {
  $u=$this->member();$this->member('taken@example.test');$this->actingAs($u);
  $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
  foreach([['new@example.test','new@example.test','wrong','email_password'],['taken@example.test','taken@example.test','An existing sunny password','email'],['broken','broken','An existing sunny password','email'],['new@example.test','other@example.test','An existing sunny password','email']] as [$email,$confirm,$password,$field]){
   $this->from('/privacy/verzoeken')->post('/profiel/email',['email'=>$email,'email_confirmation'=>$confirm,'email_password'=>$password])->assertSessionHasErrors($field)->assertSessionMissing('_old_input.email_password');
  }
  $this->assertDatabaseCount('account_email_changes',0);Notification::assertNothingSent();
 }
 public function test_confirmation_is_signed_owned_expiring_and_single_use():void {
  $u=$this->member();$url=$this->requestChange($u);$other=$this->member('other@example.test');
  $this->actingAs($other)->get($url)->assertStatus(410);
  $this->actingAs($u)->get($url.'tamper')->assertForbidden();
  $this->travel(61)->minutes();$this->post($url,['confirm'=>1])->assertForbidden();$this->assertSame('old@example.test',$u->fresh()->email);
 }
 public function test_new_request_and_cancel_invalidate_previous_links():void {
  $u=$this->member();$old=$this->requestChange($u);$new=$this->requestChange($u,'second@example.test');$this->get($old)->assertStatus(410);
  $this->post('/profiel/email/annuleren')->assertRedirect();$this->get($new)->assertStatus(410);$this->assertSame('old@example.test',$u->fresh()->email);
 }
 public function test_security_changes_and_address_taken_after_request_block_confirmation():void {
  $u=$this->member();$url=$this->requestChange($u);$this->member('new@example.test');
  $this->post($url,['confirm'=>1])->assertSessionHasErrors('email');$this->assertSame('old@example.test',$u->fresh()->email);
  $u->forceFill(['security_version'=>1])->save();$this->actingAs($u->fresh())->withSession(['account_security_version'=>1])->get($url)->assertStatus(410);
 }
 public function test_email_form_is_private_localized_and_mail_failure_keeps_old_address():void {
  $u=$this->member();$this->actingAs($u);
  foreach(['nl','en'] as $lang)$this->get('/privacy/verzoeken?lang='.$lang)->assertOk()->assertSee('old@example.test')->assertSee($lang==='nl'?'Nieuw e-mailadres':'New email address')->assertSee('name="email_confirmation"',false);
  Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail unavailable'));
  $this->post('/profiel/email',['email'=>'new@example.test','email_confirmation'=>'new@example.test','email_password'=>'An existing sunny password'])->assertSessionHasErrors('email');
  $this->assertDatabaseCount('account_email_changes',0);$this->assertSame('old@example.test',$u->fresh()->email);
 }
 public function test_guests_cannot_change_account_data():void {
  $this->post('/profiel/email',['email'=>'new@example.test'])->assertRedirect('/inloggen');$this->post('/profiel/email/annuleren')->assertRedirect('/inloggen');
 }
 public function test_requests_are_rate_limited_and_pending_addresses_are_owner_only():void {
  $u=$this->member();$this->requestChange($u);$this->requestChange($u);$this->requestChange($u);
  $this->post('/profiel/email',['email'=>'next@example.test','email_confirmation'=>'next@example.test','email_password'=>'An existing sunny password'])->assertStatus(429);
  $this->get('/privacy/verzoeken')->assertOk()->assertSee('new@example.test')->assertSee('Wijziging annuleren');
  $this->actingAs($this->member('other@example.test'))->get('/privacy/verzoeken')->assertOk()->assertDontSee('new@example.test');
 }
}
