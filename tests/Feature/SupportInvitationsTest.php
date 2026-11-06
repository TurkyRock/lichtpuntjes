<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http};
use Illuminate\Support\Str;
use App\Models\{User,SupportPledge,SupportPayment};
use App\Services\SupportInvitations;
class SupportInvitationsTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();config(['support.key'=>'test_fake']);$this->travelTo(\Carbon\Carbon::parse('2026-10-22 12:00:00','Europe/Amsterdam'));}
 private function member(int $pastVisits=4):User {$u=User::forceCreate(['name'=>'Trouwe lezer','email'=>Str::uuid().'@example.test','password'=>'LongTestPassword123','email_verified_at'=>now(),'community_accepted_at'=>now()]);for($i=1;$i<=$pastVisits;$i++)DB::table('user_visits')->insert(['user_id'=>$u->id,'visit_date'=>now('Europe/Amsterdam')->subDays($i)->toDateString()]);return $u;}
 private function pledge(User $u,array $v=[]):SupportPledge {return SupportPledge::create($v+['id'=>(string)Str::uuid(),'user_id'=>$u->id,'cents'=>500,'frequency'=>'monthly','status'=>'paid','subscription_id'=>'sub_'.Str::random(10),'subscription_status'=>'active','mode'=>'test']);}
 private function claim(string $kind='inline',string $placement='home') {return $this->postJson('/steun/uitnodiging/getoond',compact('kind','placement'));}
 public function test_first_four_visits_and_guest_without_cookie_permission_have_no_invitation():void {
  $this->get('/')->assertOk()->assertDontSee('data-support-invitation',false)->assertCookieMissing(SupportInvitations::COOKIE);
  $this->actingAs($this->member(3))->get('/')->assertOk()->assertDontSee('data-support-invitation',false);Http::assertNothingSent();
 }
 public function test_fifth_distinct_day_shows_card_and_refresh_does_not_increase_count():void {
  $u=$this->member();$this->actingAs($u)->get('/')->assertOk()->assertSee('data-kind="inline"',false);$this->get('/')->assertOk();
  $this->assertSame(5,DB::table('user_visits')->where('user_id',$u->id)->count());$this->claim()->assertJson(['show'=>true]);$this->get('/profiel')->assertOk()->assertDontSee('data-support-invitation',false);$this->claim()->assertJson(['show'=>false]);
 }
 public function test_account_overview_has_card_but_other_account_sections_and_writing_pages_do_not():void {
  $this->actingAs($this->member())->get('/profiel')->assertOk()->assertSee('data-placement="account"',false);
  $this->get('/profiel?section=badges')->assertOk()->assertDontSee('data-support-invitation',false);
  $this->get('/mijn-momentjes')->assertOk()->assertDontSee('data-support-invitation',false);
 }
 public function test_dismissal_and_support_page_visit_pause_all_placements_for_ninety_days():void {
  $u=$this->member();$this->actingAs($u)->postJson('/steun/uitnodiging/sluiten')->assertJson(['saved'=>true]);$this->get('/')->assertDontSee('data-support-invitation',false);$this->get('/profiel')->assertDontSee('data-support-invitation',false);
  $this->travel(91)->days();$this->get('/')->assertSee('data-support-invitation',false);$this->get('/steun')->assertOk();$this->get('/')->assertDontSee('data-support-invitation',false);
 }
 public function test_active_monthly_support_is_excluded_on_every_placement():void {
  $u=$this->member(15);$this->pledge($u);$this->actingAs($u)->get('/')->assertDontSee('data-support-invitation',false);$this->get('/profiel')->assertDontSee('data-support-invitation',false);$this->claim('popup')->assertJson(['show'=>false]);Http::assertNothingSent();
 }
 public function test_stopped_monthly_support_has_sixty_days_rest_and_can_be_invited_afterwards():void {
  $u=$this->member();$this->pledge($u,['stopped_at'=>now()->subDays(59),'subscription_status'=>'canceled']);$this->actingAs($u)->get('/')->assertDontSee('data-support-invitation',false);
  $this->travel(2)->days();$this->get('/')->assertSee('data-kind="inline"',false);
 }
 public function test_recent_one_time_payment_has_six_months_rest():void {
  $u=$this->member();$p=$this->pledge($u,['frequency'=>'once','subscription_id'=>null,'subscription_status'=>null]);SupportPayment::create(['pledge_id'=>$p->id,'provider_id'=>'tr_test','cents'=>500,'status'=>'paid','paid_at'=>now()->subDays(179)]);
  $this->actingAs($u)->get('/')->assertDontSee('data-support-invitation',false);$this->travel(2)->days();$this->get('/')->assertSee('data-kind="inline"',false);
 }
 public function test_popup_is_possible_from_ten_days_only_once_and_honours_global_pause():void {
  $u=$this->member(9);$this->actingAs($u)->get('/')->assertSee('data-kind="popup"',false);$this->claim('popup')->assertJson(['show'=>true]);$this->claim('popup')->assertJson(['show'=>false]);
  $this->assertGreaterThan(0,DB::table('support_invitation_preferences')->where('user_id',$u->id)->value('popup_shown_at'));$this->travel(91)->days();$this->get('/')->assertSee('data-kind="inline"',false)->assertDontSee('data-kind="popup"',false);
 }
 public function test_account_uses_inline_card_even_after_ten_days():void {$u=$this->member(12);$this->actingAs($u)->get('/profiel')->assertSee('data-kind="inline"',false);$this->claim('inline','account')->assertJson(['show'=>true]);}
 public function test_guest_counts_require_existing_cookie_permission_and_do_not_count_refreshes():void {
  $this->withCookie('lichtpunt_consent','v2|yes|'.now()->timestamp)->withCookie(SupportInvitations::COOKIE,json_encode(['visits'=>4,'last_day'=>now('Europe/Amsterdam')->toDateString(),'paused_until'=>0,'last_shown_at'=>0,'popup_shown_at'=>0]));
  $this->get('/')->assertDontSee('data-support-invitation',false);$this->travel(1)->days();$this->get('/')->assertSee('data-kind="inline"',false)->assertCookie(SupportInvitations::COOKIE);
 }
 public function test_capture_invitation_pages_when_requested():void {
  if(!getenv('INVITATION_CAPTURE')){$this->assertTrue(true);return;}$u=$this->member();$this->actingAs($u);$dir=base_path('../verification/invitation-render');@mkdir($dir,0777,true);
  foreach(['nl','en'] as $lang){$path=$lang==='en'?'/en':'/';file_put_contents($dir.'/home-'.$lang.'.html',$this->get($path)->assertOk()->getContent());file_put_contents($dir.'/account-'.$lang.'.html',$this->get('/profiel?lang='.$lang)->assertOk()->getContent());}
  DB::table('user_visits')->where('user_id',$u->id)->delete();for($i=1;$i<=9;$i++)DB::table('user_visits')->insert(['user_id'=>$u->id,'visit_date'=>now()->subDays($i)->toDateString()]);
  file_put_contents($dir.'/popup.html',$this->get('/')->assertOk()->getContent());
 }
}
