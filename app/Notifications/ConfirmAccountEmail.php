<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
class ConfirmAccountEmail extends Notification {
 public function __construct(public string $url,public string $localeCode) {}
 public function via(object $notifiable):array {return ['mail'];}
 public function toMail(object $notifiable):MailMessage {
  $en=$this->localeCode==='en';
  return (new MailMessage)->subject($en?'Confirm your new email — Jouw Lichtpuntje':'Bevestig je nieuwe e-mailadres — Jouw Lichtpuntje')
   ->greeting($en?'A new address for your light':'Een nieuw adres voor jouw lichtpuntje')
   ->line($en?'You requested a new email address for your account. Your current address remains active until you confirm.':'Je hebt een nieuw e-mailadres voor je account aangevraagd. Je huidige adres blijft werken totdat je bevestigt.')
   ->action($en?'Confirm new email':'Nieuw e-mailadres bevestigen',$this->url)
   ->line($en?'This link expires in 60 minutes. Sign in to your existing account to confirm. If you did not request this, ignore this email.':'Deze link is 60 minuten geldig. Log in op je bestaande account om te bevestigen. Heb je dit niet aangevraagd? Dan kun je deze e-mail negeren.');
 }
}
