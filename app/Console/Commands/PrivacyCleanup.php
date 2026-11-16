<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class PrivacyCleanup extends Command {
 protected $signature='privacy:cleanup {--apply}';protected $description='Controleer of verwijder verlopen afgehandelde privacy- en moderatieregistraties';
 public function handle():int {
  $queries=[
   'privacy_requests'=>DB::table('privacy_requests')->whereNotNull('resolved_at')->where('resolved_at','<',now()->subDays(config('privacy.request_days'))),
   'content_reports'=>DB::table('content_reports')->whereNotNull('resolved_at')->where('resolved_at','<',now()->subDays(config('privacy.report_days'))),
   'account_security_events'=>DB::table('account_security_events')->where('created_at','<',now()->subDays(config('privacy.security_days'))),
  ];
  foreach($queries as $name=>$q){$this->line($name.': '.$q->count());if($this->option('apply'))$q->delete();}
  foreach(DB::table('privacy_file_deletions')->limit(500)->get() as $file){
   if(!preg_match('~^(moment-images/[a-f0-9]{48}\.(jpg|webp)|profile-photos/[a-f0-9-]{36}\.jpg)$~D',$file->path))continue;
   if($this->option('apply'))try{if(\Illuminate\Support\Facades\Storage::disk('local')->delete($file->path))DB::table('privacy_file_deletions')->where('id',$file->id)->delete();}catch(\Throwable $e){$this->warn('Bestandsverwijdering wordt opnieuw geprobeerd.');}
  }
  // Unpaid abandoned checkout records contain no accounting transaction.
  $q=DB::table('support_pledges')->whereNull('subscription_id')->whereNull('stopped_at')->where('status','!=','paid')->where('updated_at','<',now()->subDays(90))->whereNotExists(fn($q)=>$q->selectRaw('1')->from('support_payments')->whereColumn('pledge_id','support_pledges.id'));
  $this->line('abandoned_checkouts: '.$q->count());if($this->option('apply'))$q->delete();return self::SUCCESS;
 }
}
