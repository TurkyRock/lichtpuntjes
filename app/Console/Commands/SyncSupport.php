<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\SupportPledge;
use App\Services\SupportPayments;
class SyncSupport extends Command {
 protected $signature='support:sync {--limit=25}';
 protected $description='Hervat bevestiging van open bijdragen na een gemiste Mollie-webhook';
 public function handle(SupportPayments $service):int {
  $limit=(string)$this->option('limit');if(!ctype_digit($limit)||(int)$limit<1||(int)$limit>100)return self::FAILURE;
  $failed=0;
  $pending=SupportPledge::where('mode',$service->mode())->whereNotNull('first_payment_id')->where(function($q){$q->whereIn('status',['pending','open','authorized'])->orWhere(fn($q)=>$q->where('frequency','monthly')->whereNull('stopped_at')->whereNull('subscription_id')->where('status','paid'));})->oldest('updated_at')->limit((int)$limit)->get();
  foreach($pending as $p){try{$service->sync($p->first_payment_id);}catch(\Throwable $e){$failed++;$this->warn('Nog niet bevestigd: '.$p->id);}}
  $this->info($pending->count().' bijdragen gecontroleerd; '.$failed.' vragen nog aandacht.');return $failed?self::FAILURE:self::SUCCESS;
 }
}
