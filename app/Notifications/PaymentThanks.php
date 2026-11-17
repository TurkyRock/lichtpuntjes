<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\SupportPayment;
use App\Support\AccountMail;
class PaymentThanks extends Notification {
    public function __construct(public SupportPayment $payment) {}
    public function via(object $notifiable):array{return ['mail'];}
    public function toMail(object $notifiable):MailMessage {
        $p=$this->payment->pledge;$test=$p->mode==='test';
        $amount='€ '.number_format($this->payment->cents/100,2,',','.');
        $message=AccountMail::message(false,'contribution')->subject(($test?'[TEST] ':'').'Dank je wel voor je bijdrage — Jouw Lichtpuntjes')
            ->greeting('Dank je wel voor jouw beetje zon!');
        if($test)$message->line('Dit is een testbetaling. Er is geen echt geld ontvangen.');
        $message->line('Je bijdrage van '.$amount.' is bevestigd. Met jouw steun help je mij Jouw Lichtpuntjes gratis en reclamevrij te houden. Dat waardeer ik enorm.');
        if($p->frequency==='monthly') {
            $message->line($p->subscription_id && $p->subscription_status==='active' && !$p->stopped_at
                ? 'Je vindt je maandelijkse afspraak en de mogelijkheid om te stoppen bij Mijn donaties.'
                : ($p->stopped_at?'Je maandelijkse afspraak is gestopt. Dank je wel voor je eerdere steun.':'Je maandelijkse afspraak is nog niet volledig bevestigd. Je eerste betaling is wel ontvangen; je hoeft niet opnieuw te betalen. Bekijk de status bij Mijn donaties.'));
        }
        return $message->action('Bekijk mijn donaties',rtrim(config('app.url'),'/').'/mijn-bijdragen?lang=nl')
            ->line('Bedankt dat je deze plek helpt stralen!');
    }
}
