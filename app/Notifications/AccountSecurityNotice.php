<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
class AccountSecurityNotice extends Notification implements ShouldQueue {
    use Queueable;
    public function __construct(public string $kind) { $this->onConnection('database')->onQueue('lichtpuntjes')->afterCommit(); }
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage {
        return (new MailMessage)->subject('Beveiligingsmelding — Lichtpuntjes')
            ->greeting('Een bericht over je account')
            ->line($this->kind==='email_changed' ? 'Het e-mailadres van je Lichtpuntjes-account is gewijzigd.' : 'Het wachtwoord van je Lichtpuntjes-account is gewijzigd. Andere ingelogde sessies zijn beëindigd.')
            ->line('Was jij dit? Dan hoef je niets te doen. Herken je dit niet? Herstel je wachtwoord en neem contact op met de beheerder.')
            ->action('Naar Lichtpuntjes', rtrim(config('app.url'),'/').'/wachtwoord-vergeten');
    }
}
