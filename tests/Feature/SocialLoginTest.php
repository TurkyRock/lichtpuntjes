<?php
namespace Tests\Feature;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialOAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SocialLoginTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array', 'session.driver'=>'array', 'app.url'=>'http://localhost', 'social-login.google.client_id'=>'test-client','social-login.google.client_secret'=>'test-secret','social-login.google.redirect'=>'http://localhost/auth/google/callback']);
        DB::purge('sqlite');
        $this->artisan('migrate', ['--force'=>true])->assertExitCode(0);
        Notification::fake();
    }
    private function pending(array $extra=[]): array {
        return array_merge(['provider'=>'google','subject'=>'google-123','email'=>'sunny@example.com','email_verified'=>true,'name'=>'Zonnetje','expires'=>time()+600,'invite'=>null], $extra);
    }
    private function member(array $extra=[]): User {
        $u=User::create(['name'=>'Bestaand','email'=>'sunny@example.com','password'=>'SunnyPassword123']);
        $u->forceFill($extra)->save();return $u;
    }
    private function input(array $extra=[]): array { return array_merge(['name'=>'Zonnetje','intentions'=>1],$extra); }
    private function identity(array $extra=[]): void {
        $identity=$this->pending($extra);
        $this->mock(SocialOAuth::class, function($m) use ($identity) {
            $m->shouldReceive('available')->with('google')->andReturn(true);
            $m->shouldReceive('sameOrigin')->andReturn(true);
            $m->shouldReceive('identity')->with('google')->andReturn($identity);
        });
    }
    public function test_unconfigured_providers_do_not_break_login(): void {
        config(['social-login.google.client_id'=>'']);
        $this->get('/inloggen')->assertOk()->assertDontSee('Doorgaan met Google');
        $this->get('/auth/google/redirect')->assertNotFound();
    }
    public function test_google_redirect_uses_state_and_minimal_scopes(): void {
        $response=$this->get('/auth/google/redirect')->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY),$query);
        $this->assertNotEmpty($query['state']);
        $this->assertSame('http://localhost/auth/google/callback',$query['redirect_uri']);
        $this->assertSame('openid profile email',$query['scope']);
        $response->assertSessionHas('state')->assertSessionHas('social.flow.provider','google');
    }
    public function test_wrong_oauth_state_does_not_create_account(): void {
        $this->withSession(['state'=>'right-state','social.flow'=>['provider'=>'google','started'=>time(),'locale'=>'nl']])->get('/auth/google/callback?state=wrong-state&code=invalid')->assertRedirect('/inloggen?lang=nl');
        $this->assertGuest();$this->assertDatabaseCount('users',0);
    }
    public function test_callback_requires_a_recent_matching_flow(): void {
        $this->get('/auth/google/callback')->assertRedirect('/inloggen?lang=nl');
        $this->withSession(['social.flow'=>['provider'=>'apple','started'=>time()]])->get('/auth/google/callback')->assertRedirect('/inloggen?lang=nl');
        $this->assertGuest();
    }
    public function test_first_sign_in_requires_explicit_community_consent(): void {
        $this->identity();
        $this->withSession(['social.flow'=>['provider'=>'google','started'=>time(),'locale'=>'en']])->get('/auth/google/callback')->assertRedirect('/aanmelden/afronden')->assertSessionHas('social.pending');
        $this->assertGuest();$this->assertDatabaseCount('users',0);
        $this->get('/aanmelden/afronden')->assertOk()->assertSee('community rules');
        $this->postJson('/aanmelden/afronden',['name'=>'Zonnetje'])->assertUnprocessable()->assertJsonValidationErrors('intentions');
        $this->assertDatabaseCount('users',0);
    }
    public function test_verified_google_creates_one_account_without_admin_or_tokens(): void {
        $this->withSession(['social.pending'=>$this->pending()])->post('/aanmelden/afronden',$this->input(['is_admin'=>1,'email'=>'attacker@example.com']))->assertRedirect('/profiel')->assertSessionMissing('social.pending');
        $u=User::firstOrFail();$this->assertAuthenticatedAs($u);
        $this->assertSame('sunny@example.com',$u->email);$this->assertTrue($u->hasVerifiedEmail());$this->assertFalse($u->is_admin);$this->assertNotNull($u->community_accepted_at);
        $this->assertDatabaseHas('social_accounts',['user_id'=>$u->id,'provider'=>'google','provider_user_id'=>'google-123']);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('social_accounts','access_token'));
    }
    public function test_existing_email_is_not_automatically_linked(): void {
        $u=$this->member();
        $this->withSession(['social.pending'=>$this->pending()])->post('/aanmelden/afronden',$this->input())->assertSessionHasErrors('existing_password');
        $this->assertGuest();$this->assertDatabaseCount('social_accounts',0);
        $this->post('/aanmelden/afronden',$this->input(['existing_password'=>'wrong']))->assertSessionHasErrors('existing_password');
        $this->post('/aanmelden/afronden',$this->input(['existing_password'=>'SunnyPassword123']))->assertRedirect('/profiel');
        $this->assertAuthenticatedAs($u);$this->assertDatabaseCount('users',1);
        $this->assertSame('Bestaand',$u->fresh()->name);
    }
    public function test_linked_account_uses_provider_id_even_when_email_changes(): void {
        $u=$this->member();SocialAccount::create(['user_id'=>$u->id,'provider'=>'google','provider_user_id'=>'google-123']);
        $this->identity(['email'=>'different@example.com']);
        $this->withSession(['social.flow'=>['provider'=>'google','started'=>time(),'locale'=>'nl']])->get('/auth/google/callback')->assertRedirect('/profiel');
        $this->assertAuthenticatedAs($u);$this->assertDatabaseCount('users',1);
    }
    public function test_suspended_linked_account_cannot_sign_in(): void {
        $u=$this->member(['is_suspended'=>true]);SocialAccount::create(['user_id'=>$u->id,'provider'=>'google','provider_user_id'=>'google-123']);$this->identity();
        $this->withSession(['social.flow'=>['provider'=>'google','started'=>time(),'locale'=>'nl']])->get('/auth/google/callback')->assertRedirect('/inloggen?lang=nl');$this->assertGuest();
    }
    public function test_unverified_email_requires_mail_confirmation(): void {
        $this->withSession(['social.pending'=>$this->pending(['provider'=>'microsoft','email_verified'=>false])])->post('/aanmelden/afronden',$this->input())->assertRedirect('/profiel');
        $u=User::firstOrFail();$this->assertFalse($u->hasVerifiedEmail());
        Notification::assertSentTo($u,\Illuminate\Auth\Notifications\VerifyEmail::class);
    }
    public function test_expired_registration_is_rejected(): void {
        $this->withSession(['social.pending'=>$this->pending(['expires'=>time()-1])])->post('/aanmelden/afronden',$this->input())->assertRedirect('/inloggen');$this->assertGuest();$this->assertDatabaseCount('users',0);
    }
    public function test_unknown_provider_and_wrong_start_origin_are_rejected(): void {
        $this->get('/auth/unknown/redirect')->assertNotFound();
        $this->get('http://lichtpunt.test/auth/google/redirect')->assertRedirect('/inloggen?lang=nl');
    }

    public function test_microsoft_redirect_and_invalid_state(): void {
        config(['social-login.microsoft.client_id'=>'test-client','social-login.microsoft.client_secret'=>'test-secret','social-login.microsoft.redirect'=>'http://localhost/auth/microsoft/callback']);
        $response=$this->get('/auth/microsoft/redirect')->assertRedirect();
        $url=$response->headers->get('Location');
        $this->assertStringStartsWith('https://login.microsoftonline.com/common/oauth2/v2.0/authorize?', $url);
        parse_str(parse_url($url,PHP_URL_QUERY),$query);
        $this->assertStringContainsString('User.Read',$query['scope']);
        $this->assertStringNotContainsString('Mail.Read',$query['scope']);
        $this->get('/auth/microsoft/callback?state=wrong&code=invalid')->assertRedirect('/inloggen?lang=nl');
        $this->assertGuest();$this->assertDatabaseCount('users',0);
    }
    public function test_apple_redirect_uses_nonce_and_secure_cross_site_cookie(): void {
        $path=tempnam(sys_get_temp_dir(),'lichtpunt-apple-test');
        file_put_contents($path,'test-key-placeholder');
        try {
            config(['social-login.apple.client_id'=>'test-service','social-login.apple.team_id'=>'test-team','social-login.apple.key_id'=>'test-key','social-login.apple.private_key'=>$path,'social-login.apple.redirect'=>'https://lichtpunt.example/auth/apple/callback']);
            $response=$this->get('https://lichtpunt.example/auth/apple/redirect')->assertRedirect();
            $url=$response->headers->get('Location');
            $this->assertStringStartsWith('https://appleid.apple.com/auth/authorize?', $url);
            parse_str(parse_url($url,PHP_URL_QUERY),$query);
            $this->assertNotEmpty($query['nonce']);$this->assertNotEmpty($query['state']);
            $this->assertSame('form_post',$query['response_mode']);
            $response->assertSessionHas('nonce')->assertSessionHas('state');
            $cookies=array_filter($response->headers->getCookies(),fn($c)=>$c->getName()===config('session.cookie'));
            $this->assertNotEmpty($cookies);$cookie=array_values($cookies)[0];
            $this->assertTrue($cookie->isSecure());$this->assertSame('none',$cookie->getSameSite());
            $this->post('https://lichtpunt.example/auth/apple/callback',['state'=>'wrong','code'=>'invalid'])->assertRedirect('/inloggen?lang=nl');
            $this->assertGuest();$this->assertDatabaseCount('users',0);
        } finally { unlink($path); }
    }
}
