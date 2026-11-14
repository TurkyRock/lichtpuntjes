<?php
namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\LightspotSubmission;
use App\Services\LightspotProcessor;
class ProcessLightspot implements ShouldQueue {
 use Queueable;
 public int $tries=0;
 public int $timeout=1800;
 public function __construct(public int $submissionId){$this->onConnection('database')->onQueue('lichtpuntjes');}
 public function handle(LightspotProcessor $processor):void {
  $s=LightspotSubmission::find($this->submissionId);if(!$s)return;
  $processor->process($s);
  $s->refresh();if(in_array($s->status,['pending','image_wait']))$this->release($s->ai_retry_at?max(60,now()->diffInSeconds(\Carbon\CarbonImmutable::parse($s->ai_retry_at),false)):(app(\App\Services\LaptopImages::class)->enabled()?60:3600));
 }
 public function failed(?\Throwable $e):void {
  $s=LightspotSubmission::find($this->submissionId);
  if($s&&$s->status==='pending')$s->update(['status'=>'doubt','ai_error'=>true,'reason'=>['nl'=>'De automatische verwerking is gestopt. Een beheerder kijkt ernaar.','en'=>'Automatic processing stopped. An administrator will review this.']]);
 }
}
