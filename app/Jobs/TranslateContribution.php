<?php
namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\ContributionTranslator;
class TranslateContribution implements ShouldQueue {
    use Queueable;
    public int $tries=0;
    public int $timeout=60;
    public int $maxExceptions=5;
    public function __construct(public string $kind,public int $id,public string $hash){$this->onConnection('database')->onQueue('lichtpuntjes');}
    public function backoff():array{return [60,300,1800,3600];}
    public function handle(ContributionTranslator $translator):void {try{$translator->translate($this->kind,$this->id,$this->hash);}catch(\App\Services\LocalAiPending $e){$this->release(60);}}
    public function failed(?\Throwable $e):void {
        $class=ContributionTranslator::model($this->kind);$m=$class::find($this->id);
        if($m&&hash_equals($this->hash,hash('sha256',$m->body)))$m->update(['translation_state'=>'failed']);
    }
}
