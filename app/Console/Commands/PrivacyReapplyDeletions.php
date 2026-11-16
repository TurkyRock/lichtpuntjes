<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use App\Services\{AccountErasure,DetachedSupport};
use App\Models\User;
class PrivacyReapplyDeletions extends Command {
 protected $signature='privacy:reapply-deletions {ledger : Private encrypted deletion ledger saved AFTER the backup} {--apply}';
 protected $description='Controleer of herstel verwijderingen na het terugzetten van een oudere back-up';
 public function handle():int {
  $path=$this->argument('ledger');if(!is_file($path)||is_link($path)){$this->error('Geen geldig privéverwijderregister.');return self::FAILURE;}
  $count=0;$handle=fopen($path,'rb');if(!$handle)return self::FAILURE;
  try{while(($line=fgets($handle))!==false){if(!trim($line))continue;$row=json_decode(Crypt::decryptString(trim($line)),true,512,JSON_THROW_ON_ERROR);$u=User::find($row['id']);
   if(!$u||!hash_equals($row['email_hash'],DetachedSupport::emailHash($u->email))||$u->created_at->toIso8601String()!==$row['created_at'])continue;
   $count++;if($this->option('apply'))app(AccountErasure::class)->erase($u,(bool)$row['keep_support']);
  }}catch(\Throwable $e){$this->error('Herstel niet afgerond. Houd de website in onderhoud en controleer register, sleutels, mail en betalingen.');return self::FAILURE;}finally{fclose($handle);}
  $this->info($count.' verwijderingen '.($this->option('apply')?'opnieuw uitgevoerd':'gevonden (preview)'));return self::SUCCESS;
 }
}
