<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class PrivacyCheck extends Command {
 protected $signature='privacy:check';protected $description='Controleer privacyvoorwaarden zonder geheime sleutels of persoonsgegevens te tonen';
 public function handle():int {
  $checks=['Lokale tekst-AI actief'=>(bool)config('local-ai.enabled'),'Beelden via laptop'=>app(\App\Services\LaptopImages::class)->enabled(),'Verzendende maildienst ingesteld'=>!in_array(config('mail.default'),['log','array'],true),'Privacytabellen beschikbaar'=>\Illuminate\Support\Facades\Schema::hasTable('contribution_consents'),'AI-sleutelhash geldig'=>(bool)preg_match('/^[a-f0-9]{64}$/D',(string)config('local-ai.token_hash'))];
  foreach($checks as $name=>$ok)$this->line(($ok?'OK: ':'ACTIE: ').$name);
  $this->warn('Handmatig bevestigen: Aventus-verwerkersafspraken en back-ups; mailbezorging; Google Analytics-overeenkomst, doorgifte-instellingen en bewaartermijn; correcte bedrijfsregistratie en eventuele aanvullende contact-/btw-gegevens.');
  return in_array(false,$checks,true)?self::FAILURE:self::SUCCESS;
 }
}
