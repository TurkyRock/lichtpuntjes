<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\{SupportPledge, User};
class SupportFamilyTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp():void { parent::setUp(); Http::preventStrayRequests();config(['app.url'=>'https://lichtpunt.example','support.enabled'=>true,'support.key'=>'test_abc123','support.recipient'=>'Testbedrijf','support.contact'=>'support@example.test']); }
    private function pledge(array $v=[]):SupportPledge { return SupportPledge::create($v+['id'=>(string)Str::uuid(),'cents'=>500,'frequency'=>'once','mode'=>'test','first_payment_id'=>'tr_first']); }
    private function response(SupportPledge $p,array $v=[]):array { return array_replace_recursive(['id'=>'tr_first','mode'=>'test','status'=>'paid','paidAt'=>now()->toIso8601String(),'amount'=>['currency'=>'EUR','value'=>'5.00'],'metadata'=>['support_pledge'=>$p->id]],$v); }
    public function test_original_family_photo_replaces_both_stock_photos_and_form_is_preserved():void {
        $r=$this->get('/steun')->assertOk()->assertSee('/images/erik-gezin.jpg',false)->assertSee('mijn lichtpuntjes',false)->assertDontSee('support-happy-friends.png')->assertDontSee('support-sunshine.png')->assertSee('Altijd gratis. Zonder reclame.');
        foreach(['name="frequency"','name="amount"','id="support-form"','name="attempt"','value="monthly"'] as $field)$r->assertSee($field,false);
        $this->get('/steun?lang=en')->assertOk()->assertSee('Always free. No advertising.')->assertSee('My family and me');Http::assertNothingSent();
    }
    public function test_verified_paid_return_shows_a_thank_you_once_per_session():void {
        $p=$this->pledge();Http::fake(['api.mollie.com/*'=>Http::response($this->response($p))]);
        $this->withSession(['support_attempt_'.$p->id=>true])->get('/steun/bedankt/'.$p->id)->assertOk()->assertSee('id="supportThanksDialog"',false)->assertSee('Wat ontzettend fijn');
        $this->get('/steun/bedankt/'.$p->id)->assertOk()->assertDontSee('id="supportThanksDialog"',false)->assertSee('Je bijdrage is ontvangen');
    }
    public function test_pending_failed_and_forged_return_parameters_never_trigger_thanks():void {
        foreach(['open','failed','canceled','expired'] as $status){
            $p=$this->pledge(['first_payment_id'=>'tr_'.Str::random(12)]);
            Http::fake(['api.mollie.com/v2/payments/'.$p->first_payment_id=>Http::response($this->response($p,['id'=>$p->first_payment_id,'status'=>$status,'paidAt'=>null]))]);
            $this->withSession(['support_attempt_'.$p->id=>true])->get('/steun/bedankt/'.$p->id.'?status=paid')->assertOk()->assertDontSee('id="supportThanksDialog"',false);
        }
    }
    public function test_unrelated_browser_or_provider_failure_gets_no_popup():void {
        $p=$this->pledge();Http::fake(['api.mollie.com/*'=>Http::sequence()->push($this->response($p))->push([],503)]);
        $this->get('/steun/bedankt/'.$p->id)->assertOk()->assertDontSee('id="supportThanksDialog"',false);
        $this->withSession(['support_attempt_'.$p->id=>true])->get('/steun/bedankt/'.$p->id)->assertOk()->assertDontSee('id="supportThanksDialog"',false)->assertSee('We controleren je bijdrage');
    }
    public function test_fully_refunded_contribution_does_not_trigger_thanks_popup():void {
        $p=$this->pledge();Http::fake(['api.mollie.com/*'=>Http::response($this->response($p,['amountRefunded'=>['value'=>'5.00']]))]);
        $this->withSession(['support_attempt_'.$p->id=>true])->get('/steun/bedankt/'.$p->id)->assertOk()->assertDontSee('id="supportThanksDialog"',false);
    }
    public function test_capture_when_requested():void {
        if(!getenv('MOMENTS_CAPTURE')){$this->assertTrue(true);return;}
        $dir=base_path('../verification/moment-render');@mkdir($dir,0777,true);
        foreach(['nl','en'] as $lang){
            config(['support.key'=>'']);file_put_contents($dir.'/support-'.$lang.'.html',$this->get('/steun?lang='.$lang)->assertOk()->getContent());config(['support.key'=>'test_abc123']);
            $p=$this->pledge(['first_payment_id'=>'tr_'.Str::random(10)]);Http::fake(['api.mollie.com/v2/payments/'.$p->first_payment_id=>Http::response($this->response($p,['id'=>$p->first_payment_id]))]);
            file_put_contents($dir.'/thanks-'.$lang.'.html',$this->withSession(['support_attempt_'.$p->id=>true])->get('/steun/bedankt/'.$p->id.'?lang='.$lang)->assertOk()->getContent());
        }
    }
}
