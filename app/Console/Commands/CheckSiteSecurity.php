<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
class CheckSiteSecurity extends Command {
    protected $signature='security:check';
    protected $description='Controleer veilige instellingen zonder wachtwoorden of API-sleutels te tonen';
    public function handle(): int {
        $publicStorage=realpath(public_path('storage'));
        $checks=[
            'Productieomgeving'=>app()->environment('production'),
            'Foutdetails uitgeschakeld'=>!config('app.debug'),
            'HTTPS in APP_URL'=>str_starts_with((string)config('app.url'),'https://'),
            'Veilige sessiecookie'=>(bool)config('session.secure'),
            'HttpOnly sessiecookie'=>(bool)config('session.http_only'),
            'Versleutelde sessieopslag'=>(bool)config('session.encrypt'),
            'Controle op gelekte wachtwoorden'=>(bool)config('account-security.check_breached_passwords'),
            'Beveiligingstabellen aanwezig'=>Schema::hasTable('account_security_events')&&Schema::hasColumn('users','security_version'),
            'Geen .env in public'=>!file_exists(public_path('.env')),
            'Geen database in public'=>!file_exists(public_path('database.sqlite'))&&!is_dir(public_path('database')),
            'Private opslag niet gedeeld'=>!$publicStorage||!in_array($publicStorage,array_filter([realpath(storage_path('app')),realpath(storage_path('app/private'))]),true),
            'Echte mailbezorging ingesteld'=>!in_array(config('mail.default'),['log','array',null]),
        ];
        $this->table(['Controle','Resultaat'],collect($checks)->map(fn($ok,$name)=>[$name,$ok?'OK':'AANDACHT'])->values()->all());
        $this->line('Dit is een lokale configuratiecontrole, geen penetratietest of controle van je hosting.');
        $this->line('Controleer ook updates, externe bereikbaarheid van privébestanden en het terugzetten van back-ups.');
        return in_array(false,$checks,true)?self::FAILURE:self::SUCCESS;
    }
}
