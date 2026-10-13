<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
class CheckUserLightspots extends Command {
 protected $signature='lichtpuntjes:controle';
 protected $description='Controleer instellingen voor eigen verhalen zonder AI-aanvragen';
 public function handle():int {
  foreach(['jobs','failed_jobs','lightspot_submissions','lightspot_reviews','lightspot_reports'] as $table)if(!Schema::hasTable($table)){$this->error('Tabel '.$table.' ontbreekt. Voer php artisan migrate uit.');return self::FAILURE;}
  if((int)config('queue.connections.database.retry_after',90)<=1800){$this->error('Zet DB_QUEUE_RETRY_AFTER=2100 in .env en voer optimize:clear uit.');return self::FAILURE;}
  if(!config('news.gemini_key')){$this->error('GEMINI_API_KEY ontbreekt voor keuring en vertaling.');return self::FAILURE;}
  $this->info('Databasewachtrij en AI-instellingen beschikbaar. Er is niets gegenereerd.');
  $this->line('Houd deze worker aan: php artisan queue:work database --queue=lichtpuntjes --timeout=1800 --tries=48 --sleep=3');return self::SUCCESS;
 }
}
