<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\LaptopImageJob;
class LaptopRetry extends Command {
 protected $signature='laptop:retry {id}';protected $description='Zet een technisch mislukte laptopopdracht opnieuw klaar';
 public function handle():int {$j=LaptopImageJob::whereKey($this->argument('id'))->where('status','failed')->first();if(!$j){$this->error('Geen mislukte opdracht met dit nummer.');return self::FAILURE;}$j->update(['status'=>'queued','claims'=>0,'available_at'=>now(),'lease_hash'=>null,'lease_until'=>null,'failure_code'=>null]);if($j->target_type==='moment')\App\Models\PositiveMoment::whereKey($j->target_id)->update(['image_state'=>'queued']);$this->info('Opdracht staat weer klaar.');return self::SUCCESS;}
}
