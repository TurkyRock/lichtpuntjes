<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class CommunityOwner extends Command {
 protected $signature='lichtpunt:admin {email : Het bestaande account van de eigenaar}';
 protected $description='Wijs precies één bestaand account aan als beheerder';
 public function handle():int {$u=User::where('email',strtolower($this->argument('email')))->first();if(!$u){$this->error('Maak eerst via de site je eigen account aan.');return self::FAILURE;}DB::transaction(function()use($u){DB::table('users')->update(['is_admin'=>false]);$u->forceFill(['is_admin'=>true,'is_suspended'=>false])->save();});$this->info('Alleen dit account heeft nu toegang tot /beheer.');return self::SUCCESS;}
}
