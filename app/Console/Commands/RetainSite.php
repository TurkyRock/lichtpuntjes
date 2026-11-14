<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\Maintenance\Retention;
class RetainSite extends Command {
 protected $signature='site:cleanup {--apply : Pas het afgesproken bewaarbeleid toe}';protected $description='Controleer of ruim verlopen technische bestanden en verwijderde momentjes op';
 public function handle(Retention $retention):int {
  if(!config('retention.enabled')){$this->warn('Automatisch onderhoud is uitgeschakeld.');return self::SUCCESS;}
  try{$report=$retention->run((bool)$this->option('apply'));$this->line(json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));return self::SUCCESS;}
  catch(\Throwable $e){report($e);$this->error('Onderhoud gestopt; bekijk het logbestand.');return self::FAILURE;}
 }
}
