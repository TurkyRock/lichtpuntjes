<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Artisan,Cache,DB,Hash,Http,Notification,Password};
use Illuminate\Support\Str;
use App\Models\User;
use App\Notifications\AccountSecurityNotice;
class AccountSecurityTest extends TestCase {
    use RefreshDatabase;
    private function member(array $data=[]):User{return User::forceCreate($data+['name'=>'Bezoeker','email'=>Str::uuid().'@example.test','password'=>'A good old password','email_verified_at'=>now(),'community_accepted_at'=>now(),'remember_token'=>'oldRememberToken']);}
    private function registration(string $password):array{return ['name'=>'Vriend','email'=>'new@example.test','password'=>$password,'password_confirmation'=>$password,'intentions'=>1];}
    public function test_breached_password_is_blocked_without_sending_the_password():void {
        config(['account-security.check_breached_passwords'=>true]);Http::preventStrayRequests();
        $password='a previously leaked phrase';$hash=strtoupper(sha1($password));
        Http::fake(['api.pwnedpasswords.com/range/*'=>Http::response(substr($hash,5).":20\r\n".str_repeat('A',35).':0')]);
        $this->postJson('/aanmelden',$this->registration($password))->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('users',0);
        Http::assertSent(fn($r)=>$r->url()==='https://api.pwnedpasswords.com/range/'.substr($hash,0,5)&&!str_contains($r->url(),$password)&&$r->body()==='');
    }
    public function test_long_phrase_is_allowed_and_short_password_rejected():void {
        Notification::fake();config(['account-security.check_breached_passwords'=>true]);Http::fake(['api.pwnedpasswords.com/range/*'=>Http::response(str_repeat('B',35).':0')]);
        $this->postJson('/aanmelden',$this->registration('Short123'))->assertUnprocessable();
        $this->post('/aanmelden',$this->registration('my sunny garden has flowers'))->assertRedirect('/profiel');
        $user=User::firstOrFail();$this->assertTrue(Hash::check('my sunny garden has flowers',$user->password));
        $this->assertDatabaseMissing('users',['password'=>'my sunny garden has flowers']);
    }
    public function test_breach_service_outage_does_not_silently_accept_password():void {
        config(['account-security.check_breached_passwords'=>true]);Http::fake(['*'=>Http::response('',503)]);
        $this->postJson('/aanmelden',$this->registration('another lovely long phrase'))->assertUnprocessable()->assertJsonValidationErrors('password');$this->assertDatabaseCount('users',0);
    }
    public function test_existing_shorter_password_can_still_log_in():void {
        $u=$this->member(['password'=>'OldShort123']);$this->post('/inloggen',['email'=>$u->email,'password'=>'OldShort123'])->assertRedirect('/profiel');$this->assertAuthenticatedAs($u);
    }
    public function test_expired_admin_confirmation_blocks_actions_until_password_is_confirmed():void {
        $admin=$this->member(['is_admin'=>true]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at'=>now()->subHours(2)->timestamp])->get('/beheer')->assertRedirect('/beveiliging/bevestigen');
        $this->postJson('/beheer/comments/999',['decision'=>'approved','note'=>'Akkoord'])->assertStatus(423);
        $this->post('/beveiliging/bevestigen',['password'=>'wrong'])->assertSessionHasErrors('password');
        $this->post('/beveiliging/bevestigen',['password'=>'A good old password'])->assertRedirect();
        $this->get('/beheer')->assertOk();
        $this->actingAs($this->member())->get('/beheer')->assertForbidden();
    }
    public function test_logout_everywhere_revokes_session_versions_and_remember_tokens():void {
        $u=$this->member();$oldVersion=(int)$u->security_version;
        $this->actingAs($u)->post('/beveiliging/overal-uitloggen',['confirm'=>1])->assertRedirect('/inloggen');$this->assertGuest();
        $fresh=$u->fresh();$this->assertSame($oldVersion+1,(int)$fresh->security_version);$this->assertNotSame('oldRememberToken',$fresh->remember_token);
        $this->actingAs($fresh)->withSession(['account_security_version'=>$oldVersion])->get('/profiel')->assertRedirect('/inloggen');$this->assertGuest();
        $this->post('/inloggen',['email'=>$fresh->email,'password'=>'A good old password'])->assertRedirect('/profiel');$this->get('/profiel')->assertOk();
    }
    public function test_remember_me_cookie_does_not_count_as_a_fresh_admin_password_confirmation():void {
        $u=$this->member(['is_admin'=>true]);
        $guard=\Illuminate\Support\Facades\Auth::guard('web');
        $name=$guard->getRecallerName();
        $cookie=$u->id.'|'.$u->getRememberToken().'|'.$u->getAuthPassword();
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withCookie($name,$cookie)->get('/beheer')->assertRedirect('/beveiliging/bevestigen');
        $this->assertAuthenticatedAs($u);
    }
    public function test_password_change_requires_current_password_and_invalidates_old_sessions():void {
        Notification::fake();$u=$this->member();
        $form=['current_password'=>'wrong','password'=>'my new sunny password phrase','password_confirmation'=>'my new sunny password phrase'];
        $this->actingAs($u)->post('/beveiliging/wachtwoord',$form)->assertSessionHasErrors('current_password');
        $this->post('/beveiliging/wachtwoord',array_replace($form,['current_password'=>'A good old password']))->assertRedirect('/inloggen');
        $this->assertTrue(Hash::check($form['password'],$u->fresh()->password));$this->assertSame(1,(int)$u->fresh()->security_version);
        Notification::assertSentTo($u,AccountSecurityNotice::class,fn($notice)=>$notice->kind==='password_changed');
        $this->assertDatabaseHas('account_security_events',['user_id'=>$u->id,'kind'=>'password_changed']);
        $this->assertGuest();
    }
    public function test_reset_password_also_revokes_sessions_and_queues_notification():void {
        Notification::fake();$u=$this->member();$token=Password::broker()->createToken($u);
        $this->post('/reset-password',['email'=>$u->email,'token'=>$token,'password'=>'reset to a new sunny phrase','password_confirmation'=>'reset to a new sunny phrase'])->assertRedirect('/inloggen');
        $this->assertSame(1,(int)$u->fresh()->security_version);Notification::assertSentTo($u,AccountSecurityNotice::class);
    }
    public function test_security_history_is_personal_and_never_contains_private_text():void {
        $u=$this->member();$other=$this->member();DB::table('account_security_events')->insert(['user_id'=>$other->id,'kind'=>'email_changed','created_at'=>now()]);
        $this->get('/beveiliging')->assertRedirect('/inloggen');$this->actingAs($u)->get('/beveiliging')->assertRedirect('/privacy/verzoeken?lang=nl#account-security');$this->get('/privacy/verzoeken')->assertOk()->assertDontSee('E-mailadres gewijzigd')->assertDontSee($other->email);
        $this->get('/beveiliging')->assertHeader('X-Robots-Tag','noindex');
    }
    public function test_configuration_check_does_not_expose_secrets_or_call_external_services():void {
        Http::preventStrayRequests();config(['app.key'=>'sensitive-key-never-print','news.gemini_key'=>'private-provider-key']);
        Artisan::call('security:check');$out=Artisan::output();$this->assertStringNotContainsString('sensitive-key-never-print',$out);$this->assertStringNotContainsString('private-provider-key',$out);Http::assertNothingSent();
    }
}
