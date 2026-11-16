<?php
namespace App\Console\Commands;
use App\Models\LocalAiJob;
use Illuminate\Console\Command;
class LocalAiStatus extends Command {
 protected $signature='local-ai:status {--retry-failed : Probeer mislukte, nog niet verlopen opdrachten opnieuw}';protected $description='Toon status lokale AI zonder privéteksten';
 public function handle():int {
  if($this->option('retry-failed'))LocalAiJob::where('status','failed')->where('expires_at','>',now())->update(['status'=>'queued','claims'=>0,'available_at'=>now(),'lease_hash'=>null,'lease_until'=>null]);
  $this->line('Lokale AI: '.(config('local-ai.enabled')?'aan':'uit').' · model: '.config('local-ai.model'));
  foreach(['queued','processing','completed','failed'] as $s)$this->line($s.': '.LocalAiJob::where('status',$s)->where('expires_at','>',now())->count());return self::SUCCESS;
 }
}
