<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\DailySpotlight;
class SelectDailySpotlight extends Command {
 protected $signature='news:daily';
 protected $description='Kies automatisch het lichtpuntje van de dag, zonder nieuwe AI-aanvraag';
 public function handle(DailySpotlight $service):int {$edition=$service->choose();if($edition)$this->info('Dagselectie '.$edition->edition_date.': #'.$edition->article_id.($edition->is_daily?'':' (uitgelicht)'));else $this->warn('Nog geen geschikt dagverhaal. Geef bestaande berichten een score met news:spotlight-score.');return self::SUCCESS;}
}
