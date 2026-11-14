<?php
namespace App\Jobs;
use App\Models\PositiveMoment;
use App\Services\{MomentIllustrator, ImageBudgetException};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
class GenerateMomentImage implements ShouldQueue
{
    use Queueable;
    public int $tries = 0;
    public int $maxExceptions = 1;
    public int $timeout = 180;
    public int $expiresAt;
    public function __construct(public int $momentId, public int $revision) {
        $this->expiresAt = now()->addDays(7)->timestamp;
        $this->onConnection('database')->onQueue('moment-images');
    }
    public function retryUntil():\DateTimeInterface { return \Carbon\CarbonImmutable::createFromTimestamp($this->expiresAt); }
    public function handle(MomentIllustrator $illustrator):void {
        try { $illustrator->generate($this->momentId, $this->revision); }
        catch (\App\Services\AiWaitException $e) {
            if($m=PositiveMoment::whereKey($this->momentId)->where('revision',$this->revision)->first()) {app(\App\Services\AiAvailability::class)->defer($m,$e);$m->update(['image_state'=>'waiting']);}
            $this->release(max(60,now()->diffInSeconds($e->retryAt,false)));
        } catch (ImageBudgetException $e) {
            PositiveMoment::whereKey($this->momentId)->where('revision',$this->revision)->whereNotNull('image_accepted_at')->where('image_state','!=','ready')->update(['image_state'=>'waiting']);
            $this->release(max(60, now('UTC')->diffInSeconds(now('UTC')->addDay()->startOfDay()->addMinutes(10))));
        } catch (\Throwable $e) {
            // Do not persist/log exceptions containing provider responses or user text.
            $this->markFailed();
        }
    }
    private function markFailed():void { PositiveMoment::whereKey($this->momentId)->where('revision',$this->revision)->whereNotNull('image_accepted_at')->where('image_state','!=','ready')->update(['image_state'=>'failed']); }
    public function failed(?\Throwable $e):void { $this->markFailed(); }
}
