<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Services\SupportPayments;
class CheckSupport extends Command {
 protected $signature='support:check';
 protected $description='Controleer de donatieconfiguratie zonder echte betalingen te starten';
 public function handle(SupportPayments $service):int {
  if(!Schema::hasTable('support_pledges')||!Schema::hasTable('support_payments')){$this->error('Voer eerst php artisan migrate uit.');return self::FAILURE;}
  if(!config('support.enabled')){$this->warn('Bijdragen zijn uitgeschakeld. De pagina toont: Bijdragen kan binnenkort.');return self::SUCCESS;}
  if(!$service->ready()){$this->error('Vul MOLLIE_API_KEY, SUPPORT_RECIPIENT en SUPPORT_CONTACT_EMAIL in. APP_URL moet het publiek bereikbare HTTPS-adres zijn.');return self::FAILURE;}
  $this->info('Configuratie aanwezig. Modus: '.$service->mode().'.');
  $this->warn('Geen verbinding of betaling getest. Test iDEAL, webhooks, maandelijks steunen en stoppen op het bereikbare testadres.');
  return self::SUCCESS;
 }
}
