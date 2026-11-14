<?php
namespace App\Services;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
class AiAvailability {
 private function key():string{return 'gemini:'.config('news.gemini_model');}
 public function check():void {
  $row=DB::table('ai_service_pauses')->where('service',$this->key())->first();
  if($row&&CarbonImmutable::parse($row->retry_at,'UTC')->isFuture())throw new AiWaitException(CarbonImmutable::parse($row->retry_at,'UTC'),$row->reason);
 }
 public function pause(int $status,array $body,?string $retryHeader=null):AiWaitException {
  // A 429 may be a minute limit. Only explicit daily quota evidence waits for reset.
  $details=$body['error']['details']??[];$daily=false;$delay=120;
  foreach($details as $detail){
   foreach($detail['violations']??[] as $v)if(preg_match('/perday|per_day|daily/i',($v['quotaId']??'').' '.($v['quotaMetric']??'')))$daily=true;
   if(preg_match('/^(\d+(?:\.\d+)?)s$/',(string)($detail['retryDelay']??''),$m))$delay=max($delay,(int)ceil((float)$m[1]));
  }
  if(is_string($retryHeader)&&ctype_digit($retryHeader))$delay=max($delay,(int)$retryHeader);
  $at=$daily?CarbonImmutable::now('America/Los_Angeles')->addDay()->startOfDay()->addMinutes(5)->utc():CarbonImmutable::now('UTC')->addSeconds(min(3600,$delay));
  $reason=$daily?'daily_quota':($status===429?'rate_limit':'unavailable');
  DB::transaction(function()use(&$at,&$reason){
   DB::table('ai_service_pauses')->insertOrIgnore(['service'=>$this->key(),'reason'=>$reason,'retry_at'=>$at,'created_at'=>now(),'updated_at'=>now()]);
   $old=DB::table('ai_service_pauses')->where('service',$this->key())->lockForUpdate()->first();
   if($old&&CarbonImmutable::parse($old->retry_at,'UTC')->greaterThan($at)){$at=CarbonImmutable::parse($old->retry_at,'UTC');$reason=$old->reason;}
   DB::table('ai_service_pauses')->where('service',$this->key())->update(['reason'=>$reason,'retry_at'=>$at,'updated_at'=>now()]);
  });
  return new AiWaitException($at,$reason);
 }
 public function defer($record,AiWaitException $e):void {$record->update(['ai_retry_at'=>$e->retryAt,'ai_wait_reason'=>$e->reason]);}
}
