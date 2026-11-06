<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,SupportPledge,SupportPayment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http,File};
use Illuminate\Support\Str;
class ContributionsPostTest extends TestCase {
 use RefreshDatabase;
 private int $sequence=0;
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();config(['support.key'=>'live_example','support.enabled'=>false]);}
 private function person():User {return User::forceCreate(['name'=>'Zonnetje','email'=>uniqid().'@example.test','password'=>'Password12345','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 private function pledge(User $u,array $values=[]):SupportPledge {return SupportPledge::create($values+['id'=>(string)Str::uuid(),'user_id'=>$u->id,'cents'=>500,'frequency'=>'once','mode'=>'live','status'=>'paid']);}
 private function payment(SupportPledge $p,array $values=[]):SupportPayment {return SupportPayment::create($values+['pledge_id'=>$p->id,'provider_id'=>'tr_private'.++$this->sequence,'cents'=>$p->cents,'status'=>'paid','paid_at'=>'2026-10-12 12:00:00','refunded_cents'=>0,'charged_back_cents'=>0]);}
 private function capture($response,string $name):void {if(!getenv('CONTRIBUTIONS_CAPTURE'))return;File::ensureDirectoryExists(base_path('../verification/contributions-render'));File::put(base_path('../verification/contributions-render/'.$name.'.html'),$response->getContent());}
 public function test_paid_donor_sees_real_history_and_monthly_management_without_repeated_solicitation():void {
  $u=$this->person();$p=$this->pledge($u,['frequency'=>'monthly','subscription_id'=>'sub_private','subscription_status'=>'active','customer_id'=>'cst_private']);$this->payment($p);$this->payment($p,['paid_at'=>'2026-09-12 12:00:00']);$this->payment($this->pledge($u,['cents'=>2500]),['paid_at'=>'2026-08-04 12:00:00']);
  $other=$this->pledge($this->person(),['cents'=>99999]);$this->payment($other);$this->payment($this->pledge($u,['cents'=>88888,'mode'=>'test']));
  $this->actingAs($u);foreach(['nl','en'] as $lang){$r=$this->get('/mijn-bijdragen?lang='.$lang.'&user_id='.$other->user_id.'&mode=test')->assertOk()->assertSee('data-contribution-state="supported"',false)->assertViewHas('netCents',3500)->assertViewHas('paidCount',3)->assertViewHas('monthlyCents',500)->assertSee('€ 35')->assertDontSee('data-support-choice',false)->assertDontSee('999,99')->assertDontSee('888,88')->assertDontSee('cst_private')->assertDontSee('tr_private')->assertSee('/mijn-bijdragen/'.$p->id.'/stop',false)->assertSee('name="confirm"',false)->assertHeader('X-Robots-Tag','noindex');$this->capture($r,'paid-'.$lang);}
  Http::assertNothingSent();$this->assertDatabaseCount('lightpoint_events',0);
 }
 public function test_empty_account_has_both_optional_choices_and_no_fake_received_amount():void {
  $this->actingAs($this->person());foreach(['nl','en'] as $lang){$r=$this->get('/mijn-bijdragen?lang='.$lang)->assertOk()->assertSee('data-contribution-state="empty"',false)->assertSee('data-support-choice="once"',false)->assertSee('data-support-choice="monthly"',false)->assertSee('/steun?frequency=monthly&amp;lang='.$lang,false)->assertDontSee('post-total-amount')->assertDontSee('post-monthly-management')->assertSee($lang==='nl'?'Je bent ook zonder bijdrage welkom.':'You are welcome with or without a contribution.');$this->capture($r,'empty-'.$lang);}Http::assertNothingSent();
 }
 public function test_refunds_chargebacks_and_unconfirmed_payments_do_not_inflate_totals():void {
  $u=$this->person();$this->payment($this->pledge($u),['refunded_cents'=>200,'charged_back_cents'=>100]);$this->payment($this->pledge($u,['cents'=>700]),['refunded_cents'=>700]);$this->payment($this->pledge($u,['cents'=>900]),['status'=>'open','paid_at'=>null]);$this->payment($this->pledge($u,['cents'=>1000]),['paid_at'=>null]);
  $r=$this->actingAs($u)->get('/mijn-bijdragen')->assertOk()->assertViewHas('netCents',200)->assertViewHas('paidCount',2)->assertViewHas('adjustedCents',1000)->assertSee('Terugbetalingen en terugboekingen')->assertSee('Terugbetaald')->assertSee('Wacht op bevestiging');$this->capture($r,'refunds-nl');
 }
 public function test_pending_monthly_attempt_is_not_claimed_as_paid_or_active_and_can_still_be_stopped():void {
  $u=$this->person();$p=$this->pledge($u,['frequency'=>'monthly','status'=>'pending']);$r=$this->actingAs($u)->get('/mijn-bijdragen')->assertOk()->assertSee('data-contribution-state="awaiting"',false)->assertViewHas('netCents',0)->assertViewHas('monthlyCents',0)->assertViewHas('paidCount',0)->assertDontSee('data-support-choice',false)->assertSee('/mijn-bijdragen/'.$p->id.'/stop',false)->assertSee('Ze tellen niet mee in je totaal.');$this->capture($r,'pending-nl');
  $this->post('/mijn-bijdragen/'.$p->id.'/stop',[])->assertSessionHasErrors('confirm');$this->assertNull($p->fresh()->stopped_at);Http::assertNothingSent();
 }
 public function test_pending_once_payments_do_not_prompt_a_second_donation():void {
  $u=$this->person();$p=$this->pledge($u,['status'=>'open']);$this->payment($p,['status'=>'open','paid_at'=>null]);
  $this->actingAs($u)->get('/mijn-bijdragen')->assertOk()->assertSee('data-contribution-state="awaiting"',false)->assertDontSee('data-support-choice',false)->assertViewHas('netCents',0)->assertViewHas('paidCount',0)->assertDontSee('name="confirm"',false);
 }
 public function test_closed_monthly_plans_are_not_shown_as_active_and_modes_are_kept_apart():void {
  $u=$this->person();$closed=$this->pledge($u,['frequency'=>'monthly','subscription_status'=>'canceled','stopped_at'=>now()]);$this->payment($closed);$test=$this->pledge($u,['frequency'=>'monthly','mode'=>'test','subscription_status'=>'active','cents'=>999]);$this->payment($test);
  $this->actingAs($u)->get('/mijn-bijdragen')->assertOk()->assertViewHas('netCents',500)->assertViewHas('monthlyCents',0)->assertDontSee('/mijn-bijdragen/'.$closed->id.'/stop',false)->assertDontSee('/mijn-bijdragen/'.$test->id.'/stop',false);
  config(['support.key'=>'test_example']);$this->get('/mijn-bijdragen')->assertOk()->assertViewHas('netCents',999)->assertSee('Testomgeving:')->assertDontSee('/mijn-bijdragen/'.$closed->id.'/stop',false);
 }
 public function test_totals_cover_all_payments_and_each_history_page_remains_reachable():void {
  $u=$this->person();$p=$this->pledge($u);for($i=0;$i<23;$i++)$this->payment($p);
  $this->actingAs($u)->get('/mijn-bijdragen?lang=en')->assertOk()->assertViewHas('netCents',11500)->assertViewHas('paidCount',23)->assertViewHas('history',fn($q)=>$q->count()===10&&$q->total()===23)->assertSee('payments_page=2',false)->assertSee('lang=en',false);
  $this->get('/mijn-bijdragen?lang=en&payments_page=3')->assertOk()->assertViewHas('history',fn($q)=>$q->count()===3);
 }
 public function test_frequency_links_preselect_only_and_do_not_grant_consent_or_charge_anything():void {
  foreach(['nl','en'] as $lang){$r=$this->get('/steun?frequency=monthly&lang='.$lang)->assertOk();$this->assertMatchesRegularExpression('/name="frequency" value="monthly" checked/',$r->getContent());$this->assertDoesNotMatchRegularExpression('/name="consent" value="1" checked/',$r->getContent());}
  $r=$this->get('/steun?frequency=invalid')->assertOk();$this->assertMatchesRegularExpression('/name="frequency" value="once" checked/',$r->getContent());$this->assertDatabaseCount('support_pledges',0);Http::assertNothingSent();
 }
}
