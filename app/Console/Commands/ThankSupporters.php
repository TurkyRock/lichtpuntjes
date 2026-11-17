<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB,Schema};
use App\Models\SupportPayment;
use App\Notifications\PaymentThanks;
class ThankSupporters extends Command {
    protected $signature='support:thank {--limit=5}';
    protected $description='Verstuur bedankmails voor nieuw bevestigde bijdragen aan geverifieerde accountadressen';
    public function handle():int {
        if(!Schema::hasColumn('support_payments','thanks_requested_at')){$this->warn('Voer eerst de migraties uit.');return self::SUCCESS;}
        if(in_array(config('mail.default'),['log','array'],true)&&!app()->environment('testing')){
            $this->warn('Bedankmails wachten op een werkende mailconfiguratie.');return self::SUCCESS;
        }
        $limit=(string)$this->option('limit');if(!ctype_digit($limit)||(int)$limit<1||(int)$limit>25)return self::FAILURE;
        $eligible=fn($q)=>$q->whereNull('thanks_sent_at')->where('thanks_attempts','<',5)
            ->whereNotNull('thanks_requested_at')->where('thanks_requested_at','>=',now()->subDays(7))
            ->where('status','paid')->whereNotNull('paid_at')->whereRaw('cents > refunded_cents + charged_back_cents')
            ->where(fn($q)=>$q->whereNull('thanks_attempted_at')->orWhere('thanks_attempted_at','<=',now()->subMinutes(15)));
        $payments=$eligible(SupportPayment::query())->whereHas('pledge',fn($q)=>$q->where('mode',app(\App\Services\SupportPayments::class)->mode())
            ->whereHas('user',fn($u)=>$u->whereNotNull('email_verified_at')))->with('pledge.user')->oldest('thanks_requested_at')->limit((int)$limit)->get();
        $sent=0;$failed=0;
        foreach($payments as $payment){
            $claimed=$eligible(SupportPayment::whereKey($payment->id))->update(['thanks_attempted_at'=>now(),'thanks_attempts'=>DB::raw('thanks_attempts + 1')]);
            if(!$claimed)continue;
            try {
                $payment->refresh();$user=$payment->pledge?->user;
                if(!$user||!$user->hasVerifiedEmail())continue;
                $user->notify(new PaymentThanks($payment));
                $payment->update(['thanks_sent_at'=>now()]);$sent++;
            }catch(\Throwable $e){$failed++;$this->warn('Bedankmail nog niet verstuurd voor betaling '.$payment->id.'.');}
        }
        $this->info($sent.' bedankmails verstuurd; '.$failed.' vragen nog aandacht.');return $failed?self::FAILURE:self::SUCCESS;
    }
}
