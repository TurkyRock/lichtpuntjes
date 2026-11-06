<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http,File};
class InviteStudioTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();}
 private function person():User {return User::forceCreate(['name'=>'Vriend','email'=>uniqid().'@example.test','password'=>'Password12345','email_verified_at'=>now(),'community_accepted_at'=>now(),'invite_code'=>bin2hex(random_bytes(16))]);}
 public function test_invitation_page_uses_own_link_and_all_sharing_options_without_tracking_or_rewards_for_clicks():void {
  $me=$this->person();$other=$this->person();$this->actingAs($me);
  foreach(['nl','en'] as $lang){
   $r=$this->get('/profiel?section=invite&lang='.$lang)->assertOk()->assertSee('id="inviteTitle"',false)->assertSee($me->invite_code)->assertDontSee($other->invite_code)->assertSee('invite-sun-envelope-v1.png')->assertSee('invite-studio.js')->assertHeader('Cache-Control','no-store, private');
   foreach(['whatsapp','facebook','linkedin','threads','x','pinterest','telegram','email'] as $key)$r->assertSee('data-invite-platform="'.$key.'"',false);
   foreach(['instagram','tiktok','messenger','signal'] as $key)$r->assertSee('data-invite-help="'.$key.'"',false);
   $r->assertDontSee('connect.facebook.net')->assertDontSee('platform.twitter.com')->assertDontSee('static.licdn.com');
   if(getenv('INVITE_CAPTURE')){File::ensureDirectoryExists(base_path('../verification/invite-render'));File::put(base_path('../verification/invite-render/invite-'.$lang.'.html'),$r->getContent());}
  }
  $this->assertDatabaseCount('lightpoint_events',0);Http::assertNothingSent();
 }
 public function test_guest_cannot_access_personal_link_and_other_account_sections_do_not_load_sharing_assets():void {
  $this->get('/profiel?section=invite')->assertRedirect('/inloggen');
  $this->actingAs($this->person())->get('/profiel?section=overview')->assertOk()->assertDontSee('data-invite-studio')->assertDontSee('invite-studio.js');
 }
}
