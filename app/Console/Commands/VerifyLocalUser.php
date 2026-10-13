<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
class VerifyLocalUser extends Command {
 protected $signature='lichtpunt:verify-local {email}';protected $description='Bevestig uitsluitend lokaal een testaccount';
 public function handle():int {if(!app()->environment('local')){$this->error('Alleen voor lokale Herd-tests.');return self::FAILURE;}$u=User::where('email',strtolower($this->argument('email')))->firstOrFail();$u->markEmailAsVerified();$this->info('Lokaal testaccount bevestigd.');return self::SUCCESS;}
}
