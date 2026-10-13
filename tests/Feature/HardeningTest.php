<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http,RateLimiter};
class HardeningTest extends TestCase {
 use RefreshDatabase;
 public function test_script_nonce_and_security_headers_match():void {
  $r=$this->get('/');$r->assertOk()->assertHeader('X-Frame-Options','DENY')->assertHeader('X-Content-Type-Options','nosniff');
  preg_match("/nonce-([^']+)/",$r->headers->get('Content-Security-Policy'),$m);$this->assertNotEmpty($m[1]);
  preg_match_all('/<script\b([^>]*)>/', $r->getContent(),$scripts);
  foreach($scripts[1] as $attributes)$this->assertStringContainsString('nonce="'.$m[1].'"',$attributes);
  $this->assertStringNotContainsString("'unsafe-inline'",explode('; style-src',$r->headers->get('Content-Security-Policy'))[0]);
  $this->assertStringContainsString('no-store',$r->headers->get('Cache-Control'));
 }
 public function test_account_limit_works_across_ip_addresses():void {
  $key='login-account:'.hash_hmac('sha256','someone@example.org',(string)config('app.key'));
  for($i=0;$i<10;$i++)RateLimiter::hit($key,900);
  $this->withServerVariables(['REMOTE_ADDR'=>'192.0.2.9'])->post('/inloggen',['email'=>'someone@example.org','password'=>'WrongPassword123'])->assertSessionHasErrors('email');
  $this->assertGuest();
 }
 public function test_daily_quota_does_not_retry():void {
  $r=Http::response(['errors'=>[['code'=>4006,'message'=>'you have used up your daily free allocation of 10,000 neurons']]],429);
  $response=new \Illuminate\Http\Client\Response($r->wait());
  $method=new \ReflectionMethod(\App\Services\CloudflareIllustrator::class,'retryDelay');
  $this->assertNull($method->invoke(app(\App\Services\CloudflareIllustrator::class),$response,0));
 }
 public function test_missing_csrf_is_rejected():void {
  $this->app->detectEnvironment(fn()=>'local');
  $this->postJson('/cookies/keuze',['statistics'=>true])->assertStatus(419);
 }
}
