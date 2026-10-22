<?php
namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\PositiveMoment;
use App\Services\MomentModerator;
class ReviewMoment implements ShouldQueue {
 use Queueable;
 public int $tries=2;public int $timeout=90;
 public function __construct(public int $momentId,public int $revision){$this->onConnection('database')->onQueue('lichtpuntjes');}
 public function handle(MomentModerator $moderator):void {if($m=PositiveMoment::find($this->momentId))$moderator->review($m,$this->revision);}
 public function failed(?\Throwable $e):void {if($m=PositiveMoment::whereKey($this->momentId)->where('revision',$this->revision)->where('status','pending')->first())$m->update(['status'=>'doubt','reason'=>['nl'=>'De controle is gestopt. Probeer later opnieuw.','en'=>'Review stopped. Please retry later.']]);}
}
