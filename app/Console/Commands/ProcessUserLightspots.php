<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\LightspotSubmission;
use App\Jobs\ProcessLightspot;
class ProcessUserLightspots extends Command {
 protected $signature='lichtpuntjes:hervat {--limit=5 : Maximaal aantal wachtende verhalen opnieuw klaarzetten}';
 protected $description='Zet wachtende eigen lichtpuntjes opnieuw in de achtergrondwachtrij';
 public function handle():int {
  $limit=(string)$this->option('limit');if(!ctype_digit($limit)||(int)$limit<1||(int)$limit>20){$this->error('--limit moet 1–20 zijn.');return self::FAILURE;}
  $s=LightspotSubmission::whereIn('status',['pending','image_wait'])->whereHas('user',fn($q)=>$q->where('is_suspended',false))->oldest()->limit((int)$limit)->get();
  foreach($s as $row)ProcessLightspot::dispatch($row->id);$this->info($s->count().' verhalen klaargezet. Start de lichtpuntjes-queueworker om ze te verwerken.');return self::SUCCESS;
 }
}
