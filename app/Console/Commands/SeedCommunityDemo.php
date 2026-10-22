<?php
namespace App\Console\Commands;
use App\Services\CommunityDemo;
use Illuminate\Console\Command;
class SeedCommunityDemo extends Command {
 protected $signature='lichtpunt:voorbeeldgebruikers {--verwijderen : Verwijder uitsluitend deze vier voorbeeldaccounts en hun testinhoud} {--reset-wachtwoorden : Geef de vier bestaande voorbeeldaccounts nieuwe willekeurige wachtwoorden}';
 protected $description='Vier lokale voorbeeldgebruikers met AI-portretten, momentjes en activiteit';
 public function handle(CommunityDemo $demo):int {
  if($this->option('verwijderen')&&$this->option('reset-wachtwoorden')){$this->error('Kies verwijderen of wachtwoorden vernieuwen.');return self::FAILURE;}
  try{
   if($this->option('verwijderen')){$this->info($demo->remove().' voorbeeldaccounts en hun testinhoud verwijderd.');return self::SUCCESS;}
   $r=$demo->install((bool)$this->option('reset-wachtwoorden'));
   $this->info('4 fictieve voorbeeldgebruikers, 4 gedeelde momentjes en 4 voorbeeldverhalen staan klaar.');
   $this->line('Inclusief profielfoto’s, reacties, leesactiviteit, likes, hartjes, kalender en lichtpunten.');
   if($r['credentials']){$this->warn('Bewaar deze lokale testwachtwoorden; ze worden alleen nu getoond. Geen beheerrechten.');$this->table(['Naam','E-mail','Wachtwoord'],array_map(fn($c)=>[$c['name'],$c['email'],$c['password']],$r['credentials']));}
   else $this->line('Bestaande voorbeelden hergebruikt. Voor nieuwe testwachtwoorden: --reset-wachtwoorden.');
   $this->line('Homepage: '.url('/').'; gebruikersbeheer: '.url('/beheer/gebruikers'));
   return self::SUCCESS;
  }catch(\Throwable $e){$this->error(get_class($e)===\RuntimeException::class?$e->getMessage():'Voorbeelden plaatsen mislukt. Controleer database, migraties en schrijfrechten.');return self::FAILURE;}
 }
}
