<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\Social\Planner;
use App\Models\SocialPublication;
class PrepareSocialPublications extends Command {
 protected $signature='social:prepare';protected $description='Bereid de sociale dagselecties voor zonder naar kanalen te publiceren';
 public function handle(Planner $planner):int {
  if(!config('social-publishing.planner_enabled')){$this->info('Automatisch voorbereiden staat uit. Kanalen zijn nog niet gekoppeld.');return self::SUCCESS;}
  $day=now('Europe/Amsterdam')->toDateString();foreach(config('social-publishing.slots') as $kind=>$slot){if(now()->lt($planner->due($day,$kind)))continue;
   $p=SocialPublication::whereDate('edition_date',$day)->where('kind',$kind)->first();if($p&&($p->first_shared_at||($p->status==='ready'&&$planner->usable($p->article))))continue;
   $planner->prepare($day,$kind);
  }$this->info('Selecties voorbereid. Er zijn geen posts verstuurd.');return self::SUCCESS;
 }
}
