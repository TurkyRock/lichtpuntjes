<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\{SupportPledge,User};
use App\Services\SupportPayments;
class SupportPaymentsTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();config(['app.url'=>'https://lichtpunt.example','support.enabled'=>true,'support.key'=>'test_abc123','support.recipient'=>'Testbedrijf','support.contact'=>'support@example.test']);Http::preventStrayRequests();}
 private function member(array $v=[]):User{return User::forceCreate($v+['name'=>'Steuner','email'=>Str::uuid().'@example.test','password'=>'testpassword1234','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 private function pledge(array $v=[]):SupportPledge{return SupportPledge::create($v+['id'=>(string)Str::uuid(),'cents'=>500,'frequency'=>'once','mode'=>'test','first_payment_id'=>'tr_first']);}
 private function payment(SupportPledge $p,array $v=[]):array{return array_replace_recursive(['id'=>'tr_first','mode'=>'test','status'=>'paid','paidAt'=>'2026-10-19T12:00:00+00:00','amount'=>['currency'=>'EUR','value'=>'5.00'],'metadata'=>['support_pledge'=>$p->id]],$v);}
 public function test_public_and_english_pages_and_personal_note():void {
  config(['support.key'=>'']);$this->get('/steun')->assertOk()->assertSee('überhaupt')->assertSee('nieuwe interessante functies')->assertSee('Bijdragen kan binnenkort')->assertSee('site-support');$this->get('/steun?lang=en')->assertOk()->assertSee('A little note from me');Http::assertNothingSent();
 }
 public function test_amounts_are_exact_and_invalid_values_are_rejected():void {
  $this->assertSame(1000000,SupportPayments::cents('10000'));$this->assertSame(1000001,SupportPayments::cents('10000,01'));$this->assertSame(201,SupportPayments::cents('2,01'));$this->assertSame(210,SupportPayments::cents('2.1'));
  foreach(['0.99','10000000.00','-1','1e2','2.001','1,2.3','NaN'] as $value){try{SupportPayments::cents($value);$this->fail($value);}catch(\InvalidArgumentException $e){$this->assertNotEmpty($e->getMessage());}}
 }
 public function test_once_checkout_and_repeated_submission_are_idempotent():void {
  $id=(string)Str::uuid();Http::fake(['api.mollie.com/v2/payments'=>Http::response(['id'=>'tr_first','_links'=>['checkout'=>['href'=>'https://www.mollie.com/checkout/test']]],201)]);
  $form=['attempt'=>$id,'amount'=>'10000,00','frequency'=>'once'];$this->withSession(['support_attempt_'.$id=>true])->post('/steun/betalen',$form)->assertRedirect('https://www.mollie.com/checkout/test');$this->post('/steun/betalen',$form)->assertRedirect();$this->assertDatabaseCount('support_pledges',1);Http::assertSentCount(1);
  Http::assertSent(fn($r)=>$r['amount']['value']==='10000.00'&&$r['sequenceType']==='oneoff'&&isset($r->headers()['Idempotency-Key']));
 }
 public function test_disabled_payments_and_invalid_form_never_contact_provider():void {
  $this->post('/steun/betalen',['attempt'=>(string)Str::uuid(),'amount'=>'5','frequency'=>'once'])->assertStatus(419);
  config(['support.enabled'=>false]);$this->post('/steun/betalen')->assertStatus(503);Http::assertNothingSent();
 }
 public function test_monthly_requires_consent_and_login():void {
  $id=(string)Str::uuid();$data=['attempt'=>$id,'amount'=>'5','frequency'=>'monthly'];$this->withSession(['support_attempt_'.$id=>true])->post('/steun/betalen',$data)->assertSessionHasErrors('consent');$this->post('/steun/betalen',$data+['consent'=>'1'])->assertRedirect('/inloggen');Http::assertNothingSent();
 }
 public function test_return_url_does_not_prove_payment():void {
  $p=$this->pledge();Http::fake(['api.mollie.com/v2/payments/tr_first'=>Http::response($this->payment($p,['status'=>'open','paidAt'=>null]))]);$this->get('/steun/bedankt/'.$p->id.'?status=paid')->assertOk()->assertSee('wordt verwerkt')->assertDontSee('Je bijdrage is ontvangen');
 }
 public function test_verified_webhook_is_deduplicated_and_refunds_are_recorded():void {
  $p=$this->pledge();Http::fake(['api.mollie.com/v2/payments/tr_first'=>Http::response($this->payment($p,['amountRefunded'=>['value'=>'2.00'],'amountChargedBack'=>['value'=>'1.00']]))]);$this->post('/steun/mollie/webhook',['id'=>'tr_first'])->assertOk();$this->post('/steun/mollie/webhook',['id'=>'tr_first'])->assertOk();$this->assertDatabaseCount('support_payments',1);$this->assertDatabaseHas('support_payments',['status'=>'paid','cents'=>500,'refunded_cents'=>200,'charged_back_cents'=>100]);
 }
 public function test_wrong_amount_mode_currency_and_customer_are_rejected():void {
  $p=$this->pledge(['customer_id'=>'cst_right']);foreach([['amount'=>['value'=>'6.00']],['mode'=>'live'],['amount'=>['currency'=>'USD']],['customerId'=>'cst_wrong']] as $change){Http::fake(['api.mollie.com/v2/payments/tr_first'=>Http::response($this->payment($p,$change+['customerId'=>'cst_right']))]);$this->post('/steun/mollie/webhook',['id'=>'tr_first'])->assertStatus(503);}$this->assertDatabaseCount('support_payments',0);
 }
 public function test_monthly_first_payment_creates_one_subscription_next_month():void {
  $p=$this->pledge(['frequency'=>'monthly','customer_id'=>'cst_one','consented_at'=>now()]);Http::fake([
   'api.mollie.com/v2/payments/tr_first'=>Http::response($this->payment($p,['customerId'=>'cst_one'])),
   'api.mollie.com/v2/customers/cst_one/mandates'=>Http::response(['_embedded'=>['mandates'=>[['id'=>'mdt_one','method'=>'directdebit','status'=>'valid']]]]),
   'api.mollie.com/v2/customers/cst_one/subscriptions'=>Http::response(['id'=>'sub_one','status'=>'active'],201),
   'api.mollie.com/v2/customers/cst_one/subscriptions/sub_one'=>Http::response(['id'=>'sub_one','status'=>'active'])]);
  $this->post('/steun/mollie/webhook',['id'=>'tr_first'])->assertOk();$this->post('/steun/mollie/webhook',['id'=>'tr_first'])->assertOk();$requests=Http::recorded(fn($r)=>$r->method()==='POST'&&str_ends_with($r->url(),'/subscriptions'));$this->assertCount(1,$requests);$this->assertSame('2026-11-19',$requests->first()[0]['startDate']);
 }
 public function test_recurring_payment_belongs_to_verified_subscription():void {
  $p=$this->pledge(['frequency'=>'monthly','customer_id'=>'cst_one','subscription_id'=>'sub_one']);Http::fake(['api.mollie.com/v2/payments/tr_next'=>Http::response($this->payment($p,['id'=>'tr_next','customerId'=>'cst_one','subscriptionId'=>'sub_one'])),'api.mollie.com/v2/customers/cst_one/subscriptions/sub_one'=>Http::response(['status'=>'active'])]);$this->post('/steun/mollie/webhook',['id'=>'tr_next'])->assertOk();$this->assertDatabaseHas('support_payments',['provider_id'=>'tr_next']);
 }
 public function test_stopping_pending_pledge_prevents_late_subscription_creation():void {
  $u=$this->member();$p=$this->pledge(['user_id'=>$u->id,'frequency'=>'monthly']);$this->actingAs($u)->post('/mijn-bijdragen/'.$p->id.'/stop',['confirm'=>'1'])->assertRedirect();Http::fake(['api.mollie.com/v2/payments/tr_first'=>Http::response($this->payment($p))]);$this->post('/steun/mollie/webhook',['id'=>'tr_first'])->assertOk();Http::assertSentCount(1);$this->assertNotNull($p->fresh()->stopped_at);
 }
 public function test_stop_requires_ownership_and_successful_provider_response():void {
  $u=$this->member();$p=$this->pledge(['user_id'=>$u->id,'frequency'=>'monthly','customer_id'=>'cst_one','subscription_id'=>'sub_one']);$this->actingAs($this->member())->post('/mijn-bijdragen/'.$p->id.'/stop',['confirm'=>'1'])->assertForbidden();Http::assertNothingSent();
  Http::fake(['api.mollie.com/v2/customers/cst_one/subscriptions/sub_one'=>Http::sequence()->push([],500)->push(['status'=>'active'])->push(['status'=>'canceled'])]);$this->actingAs($u)->post('/mijn-bijdragen/'.$p->id.'/stop',['confirm'=>'1'])->assertSessionHasErrors('stop');$this->assertNull($p->fresh()->stopped_at);
  $this->post('/mijn-bijdragen/'.$p->id.'/stop',['confirm'=>'1'])->assertRedirect();$this->assertNotNull($p->fresh()->stopped_at);
 }
 public function test_account_and_admin_data_remain_private():void {
  $this->pledge(['user_id'=>$this->member()->id,'cents'=>777]);$this->get('/mijn-bijdragen')->assertRedirect('/inloggen');$this->actingAs($this->member())->get('/mijn-bijdragen')->assertOk()->assertDontSee('7,77');$this->get('/beheer/bijdragen')->assertForbidden();$this->actingAs($this->member(['is_admin'=>true]))->get('/beheer/bijdragen')->assertOk()->assertSee('7,77');
 }
 public function test_monthly_checkout_creates_customer_and_first_payment():void {
  $u=$this->member();$id=(string)Str::uuid();Http::fake(['api.mollie.com/v2/customers'=>Http::response(['id'=>'cst_one'],201),'api.mollie.com/v2/payments'=>Http::response(['id'=>'tr_first','_links'=>['checkout'=>['href'=>'https://www.mollie.com/checkout/test']]],201)]);
  $this->actingAs($u)->withSession(['support_attempt_'.$id=>true])->post('/steun/betalen',['attempt'=>$id,'amount'=>'5','frequency'=>'monthly','consent'=>'1'])->assertRedirect();
  Http::assertSent(fn($r)=>str_ends_with($r->url(),'/payments')&&$r['sequenceType']==='first'&&$r['customerId']==='cst_one');$this->assertNotNull(SupportPledge::find($id)->consented_at);
 }
 public function test_private_payment_pages_are_not_indexed_and_checkout_csp_allows_mollie():void {
  $this->get('/steun')->assertHeader('Content-Security-Policy');$p=$this->pledge(['first_payment_id'=>null]);$this->get('/steun/bedankt/'.$p->id)->assertHeader('X-Robots-Tag','noindex')->assertHeader('Referrer-Policy','no-referrer');
  $this->actingAs($this->member())->get('/mijn-bijdragen')->assertHeader('X-Robots-Tag','noindex');
 }
 public function test_configuration_check_is_read_only_and_pending_sync_is_recoverable():void {
  config(['support.enabled'=>false]);$this->artisan('support:check')->assertExitCode(0);config(['support.enabled'=>true]);$this->artisan('support:check')->assertExitCode(0);Http::assertNothingSent();
  $p=$this->pledge();Http::fake(['api.mollie.com/v2/payments/tr_first'=>Http::response($this->payment($p))]);$this->artisan('support:sync')->assertExitCode(0);$this->assertDatabaseHas('support_pledges',['id'=>$p->id,'status'=>'paid']);
 }
}
