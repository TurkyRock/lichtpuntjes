<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
class ResetDemoPassword extends Command {
 protected $signature='lichtpunt:demo-wachtwoord';protected $description='Maak een nieuw wachtwoord voor het bestaande, actieve Erik Demo-testaccount';
 public function handle():int {
  $password=Str::password(20);
  $ok=DB::transaction(function()use($password){
   $u=User::where('email','erik.demo@lichtpunt.test')->lockForUpdate()->first();
   if(!$u||$u->is_admin||$u->is_suspended||!$u->hasVerifiedEmail()||!$u->community_accepted_at)return false;
   $u->forceFill(['password'=>$password,'remember_token'=>Str::random(60)])->save();return true;
  });
  if(!$ok){$this->error('Geen bestaand actief demoaccount gevonden, of dit adres is een beheeraccount. Er is niets gewijzigd.');return self::FAILURE;}
  $this->line('E-mail: erik.demo@lichtpunt.test');$this->line('Nieuw wachtwoord: '.$password);$this->info('Demo-inhoud behouden. Eerdere sessies worden ongeldig.');return self::SUCCESS;
 }
}
