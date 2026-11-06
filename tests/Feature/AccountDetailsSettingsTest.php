<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http,File};
class AccountDetailsSettingsTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();}
 private function person(bool $admin=false):User {return User::forceCreate(['name'=>'Zonnetje','email'=>uniqid().'@example.test','password'=>'An existing sunny password','email_verified_at'=>now(),'community_accepted_at'=>now(),'is_admin'=>$admin]);}
 public function test_my_details_combines_existing_editors_and_security_forms_without_an_extra_menu_item():void {
  $me=$this->person();$other=$this->person();DB::table('account_security_events')->insert(['user_id'=>$me->id,'kind'=>'password_changed','created_at'=>now()]);DB::table('account_security_events')->insert(['user_id'=>$other->id,'kind'=>'email_changed','created_at'=>now()]);
  DB::table('privacy_requests')->insert(['user_id'=>$other->id,'kind'=>'other','message'=>'PRIVATE_OTHER_REQUEST','created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($me);
  foreach(['nl','en'] as $lang){$r=$this->get('/privacy/verzoeken?lang='.$lang)->assertOk()->assertSee('<h1>'.($lang==='nl'?'Mijn gegevens':'My details').'</h1>',false)->assertSee('action="/profiel/naam?',false)->assertSee('action="/profiel/foto?',false)->assertSee('action="/beveiliging/wachtwoord"',false)->assertSee('action="/beveiliging/overal-uitloggen"',false)->assertSee($lang==='nl'?'Wachtwoord gewijzigd':'Password changed')->assertDontSee($lang==='nl'?'E-mailadres gewijzigd':'Email changed')->assertDontSee('PRIVATE_OTHER_REQUEST')->assertDontSee('href="/beveiliging?',false)->assertHeader('X-Robots-Tag','noindex');
   $this->assertSame(1,substr_count($r->getContent(),'name="current_password"'));
   if(getenv('DETAILS_CAPTURE')){File::ensureDirectoryExists(base_path('../verification/details-render'));File::put(base_path('../verification/details-render/details-'.$lang.'.html'),$r->getContent());}
   $this->get('/beveiliging?lang='.$lang)->assertRedirect('/privacy/verzoeken?lang='.$lang.'#account-security');
  }
  Http::assertNothingSent();
 }
 public function test_settings_are_authenticated_and_admin_privacy_review_has_no_personal_editors():void {
  $this->get('/privacy/verzoeken')->assertRedirect('/inloggen');$this->get('/beveiliging')->assertRedirect('/inloggen');
  $this->actingAs($this->person(true))->get('/beheer/gebruikers?filter=privacy')->assertOk()->assertDontSee('action="/beveiliging/wachtwoord"',false)->assertDontSee('name="current_password"',false);
 }
 public function test_password_validation_still_returns_to_my_details_and_logout_requires_confirmation():void {
  $u=$this->person();$this->actingAs($u)->from('/privacy/verzoeken?lang=nl')->post('/beveiliging/wachtwoord',['current_password'=>'wrong','password'=>'A new and very sunny password','password_confirmation'=>'A new and very sunny password'])->assertRedirect('/privacy/verzoeken?lang=nl')->assertSessionHasErrors('current_password');
  $this->assertAuthenticatedAs($u);$this->post('/beveiliging/overal-uitloggen',[])->assertSessionHasErrors('confirm');$this->assertAuthenticatedAs($u);
 }
}
