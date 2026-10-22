<?php
namespace App\Services;
use App\Models\{SupportPledge,SupportPayment};
use Illuminate\Support\Facades\{Cache,Http};
use Illuminate\Support\Carbon;
class SupportPayments {
 public function mode():string{return str_starts_with((string)config('support.key'),'live_')?'live':'test';}
 public function ready():bool {
  return (bool)config('support.enabled') && preg_match('/^(test|live)_[a-zA-Z0-9]+$/',(string)config('support.key'))
   && trim((string)config('support.recipient'))!=='' && filter_var(config('support.contact'),FILTER_VALIDATE_EMAIL)
   && str_starts_with((string)config('app.url'),'https://');
 }
 public static function cents(string $value):int {
  if(!preg_match('/^\d{1,7}(?:[.,]\d{1,2})?$/',$value))throw new \InvalidArgumentException('Ongeldig bedrag.');
  $parts=explode('.',str_replace(',','.',$value));$cents=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
  if($cents<100)throw new \InvalidArgumentException('Kies een bedrag vanaf €1.');return $cents;
 }
 private function call(string $method,string $path,array $data=[],?string $key=null):array {
  $http=Http::withToken(config('support.key'))->acceptJson()->timeout(15)->connectTimeout(5);
  if($key)$http=$http->withHeaders(['Idempotency-Key'=>$key]);
  $response=$http->send($method,'https://api.mollie.com/v2/'.$path,$data?['json'=>$data]:[]);
  if(!$response->successful())throw new \RuntimeException('Betalen is tijdelijk niet beschikbaar. Probeer het later opnieuw.');
  return $response->json()??[];
 }
 public function checkout(SupportPledge $p):string {
  return Cache::lock('support-'.$p->id,120)->block(5,function()use($p){
   $p->refresh();if($p->checkout_url)return $p->checkout_url;
   if($p->frequency==='monthly'&&!$p->customer_id){
    $customer=$this->call('POST','customers',['name'=>$p->user->name,'email'=>$p->user->email],$p->id.'-customer');
    $p->update(['customer_id'=>$customer['id']]);
   }
   $base=rtrim(config('app.url'),'/');
   $data=['amount'=>['currency'=>'EUR','value'=>number_format($p->cents/100,2,'.','')],
    'description'=>'Steun Lichtpuntjes '.substr($p->id,0,8),'redirectUrl'=>$base.'/steun/bedankt/'.$p->id,
    'webhookUrl'=>$base.'/steun/mollie/webhook','metadata'=>['support_pledge'=>$p->id],
    'sequenceType'=>$p->frequency==='monthly'?'first':'oneoff','method'=>'ideal'];
   if($p->customer_id)$data['customerId']=$p->customer_id;
   $payment=$this->call('POST','payments',$data,$p->id.'-payment');
   $url=$payment['_links']['checkout']['href']??'';
   if(parse_url($url,PHP_URL_SCHEME)!=='https'||!in_array(parse_url($url,PHP_URL_HOST),['www.mollie.com','mollie.com','checkout.mollie.com'],true))throw new \RuntimeException('Geen geldige betaalpagina ontvangen.');
   $p->update(['first_payment_id'=>$payment['id'],'checkout_url'=>$url]);return $url;
  });
 }
 public function sync(string $id):void {
  if(!preg_match('/^tr_[A-Za-z0-9]+$/',$id))throw new \InvalidArgumentException('Ongeldige betaling.');
  $payment=$this->call('GET','payments/'.$id);
  $pledgeId=$payment['metadata']['support_pledge']??null;
  if(!is_string($pledgeId))return;
  $p=SupportPledge::find($pledgeId);if(!$p)return;
  Cache::lock('support-'.$p->id,120)->block(5,function()use($p,$payment,$id){
   $p->refresh();
   if(($payment['mode']??'')!==$p->mode||$p->mode!==$this->mode()||($payment['amount']['currency']??'')!=='EUR')throw new \RuntimeException('Betaling kon niet worden gecontroleerd.');
   $cents=self::cents((string)$payment['amount']['value']);
   if($cents!==$p->cents)throw new \RuntimeException('Bedrag komt niet overeen.');
   $first=$id===$p->first_payment_id;
   if(!$first&&(!$p->subscription_id||($payment['subscriptionId']??null)!==$p->subscription_id))throw new \RuntimeException('Onbekende betaling.');
   if($p->customer_id&&($payment['customerId']??null)!==$p->customer_id)throw new \RuntimeException('Onbekende betaler.');
   $status=$payment['status'];
   SupportPayment::updateOrCreate(['provider_id'=>$id],['pledge_id'=>$p->id,'cents'=>$cents,'status'=>$status,
    'paid_at'=>isset($payment['paidAt'])?Carbon::parse($payment['paidAt']):null,
    'refunded_cents'=>(int)round((float)($payment['amountRefunded']['value']??0)*100),
    'charged_back_cents'=>(int)round((float)($payment['amountChargedBack']['value']??0)*100)]);
   if($first)$p->update(['status'=>$status]);
   if($first&&$status==='paid'&&$p->frequency==='monthly'&&!$p->subscription_id&&!$p->stopped_at){
    $mandates=$this->call('GET','customers/'.$p->customer_id.'/mandates');
    $mandate=collect($mandates['_embedded']['mandates']??[])->first(fn($m)=>in_array($m['status']??'', ['valid','pending'])&&($m['method']??'')==='directdebit');
    if(!$mandate)throw new \RuntimeException('De toestemming voor maandelijks steunen is nog niet bevestigd.');
    $start=Carbon::parse($payment['paidAt'])->timezone('Europe/Amsterdam')->addMonthNoOverflow()->format('Y-m-d');
    $sub=$this->call('POST','customers/'.$p->customer_id.'/subscriptions',[
     'amount'=>$payment['amount'],'interval'=>'1 month','startDate'=>$start,'method'=>'directdebit','mandateId'=>$mandate['id'],
     'description'=>'Steun Lichtpuntjes '.substr($p->id,0,8),'metadata'=>['support_pledge'=>$p->id],
     'webhookUrl'=>rtrim(config('app.url'),'/').'/steun/mollie/webhook'], $p->id.'-subscription');
    $p->update(['subscription_id'=>$sub['id'],'subscription_status'=>$sub['status']]);
   }
   if($p->subscription_id&&!$p->stopped_at){
    $sub=$this->call('GET','customers/'.$p->customer_id.'/subscriptions/'.$p->subscription_id);
    $p->update(['subscription_status'=>$sub['status']]);
   }
  });
 }
 public function stop(SupportPledge $p):void {
  Cache::lock('support-'.$p->id,120)->block(5,function()use($p){
   $p->refresh();if($p->stopped_at)return;
   if($p->mode!==$this->mode())throw new \RuntimeException('Gebruik de bijbehorende Mollie-test- of livesleutel om deze bijdrage te stoppen.');
   if($p->subscription_id){
    $path='customers/'.$p->customer_id.'/subscriptions/'.$p->subscription_id;
    $sub=$this->call('GET',$path);
    if(!in_array($sub['status'],['canceled','completed']))$this->call('DELETE',$path);
   }
   $p->update(['stopped_at'=>now(),'subscription_status'=>'canceled']);
  });
 }
}
