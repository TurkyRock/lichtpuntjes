<?php
namespace App\Console\Commands;
use App\Services\SocialOAuth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
class SocialLoginCheck extends Command {
    protected $signature = 'lichtpunt:social-check';
    protected $description = 'Check social sign-in configuration without printing secrets';
    public function handle(SocialOAuth $oauth): int {
        $this->table(['Aanbieder','Configuratie','Callback'], array_map(fn($p) => [ucfirst($p), $oauth->available($p) ? 'Gereed voor test' : 'Niet compleet: zie LEESMIJ-SOCIAAL.txt', config("social-login.$p.redirect")], SocialOAuth::PROVIDERS));
        $this->line(Schema::hasTable('social_accounts') ? 'Database: gereed' : 'Database: voer php artisan migrate uit');
        $this->line('Dit controleert configuratie, geen echte inlogpoging. Open de site op hetzelfde adres als de callback.');
        return self::SUCCESS;
    }
}
