<?php
namespace App\Support;

use Illuminate\Notifications\Messages\MailMessage;

class AccountMail
{
    public static function english():bool
    {
        // Dutch is the default, including CLI/queue execution. An explicit
        // English website choice is retained for messages requested there.
        return app()->bound('request') && request()->hasSession()
            && request()->session()->get('lichtpunt_locale')==='en';
    }

    public static function message(bool $en=false,string $purpose='account'):MailMessage
    {
        return (new MailMessage)->view(['html'=>'emails.account','text'=>'emails.account-text'],['mailEn'=>$en,'mailPurpose'=>$purpose]);
    }

    public static function reset(object $user,string $token):MailMessage
    {
        $en=self::english();
        $url=url(route('password.reset',['token'=>$token,'email'=>$user->getEmailForPasswordReset(),'lang'=>$en?'en':'nl'],false));
        $minutes=(int)config('auth.passwords.'.config('auth.defaults.passwords').'.expire',60);
        return self::message($en)->subject($en?'Reset your password — Jouw Lichtpuntjes':'Stel je nieuwe wachtwoord in — Jouw Lichtpuntjes')
            ->greeting($en?'A fresh start for your account':'Een frisse start voor je account')
            ->line($en?'You requested a new password for your Jouw Lichtpuntjes account. Use the button below to choose a new password.':'Je hebt een nieuw wachtwoord aangevraagd voor je account bij Jouw Lichtpuntjes. Via de knop hieronder kies je een nieuw wachtwoord.')
            ->action($en?'Choose a new password':'Kies een nieuw wachtwoord',$url)
            ->line($en?"This personal link is valid for {$minutes} minutes.":"Deze persoonlijke link is {$minutes} minuten geldig.")
            ->line($en?'Did not request this? You can safely ignore this email. Your password stays unchanged.':'Heb je dit niet aangevraagd? Dan kun je deze e-mail gerust negeren. Je wachtwoord blijft ongewijzigd.');
    }

    public static function verify(string $url):MailMessage
    {
        $en=self::english();
        $minutes=(int)config('auth.verification.expire',60);
        return self::message($en)->subject($en?'Welcome! Confirm your email — Jouw Lichtpuntjes':'Welkom! Bevestig je e-mailadres — Jouw Lichtpuntjes')
            ->greeting($en?'Welcome. Bring your smile!':'Welkom. Breng je glimlach mee!')
            ->line($en?'How lovely that you are joining Jouw Lichtpuntjes. Confirm your email address to get your account ready.':'Wat fijn dat je meedoet met Jouw Lichtpuntjes. Bevestig je e-mailadres om je account klaar te maken.')
            ->action($en?'Confirm my email address':'Bevestig mijn e-mailadres',$url)
            ->line($en?"This personal link is valid for {$minutes} minutes.":"Deze persoonlijke link is {$minutes} minuten geldig.")
            ->line($en?'Did not create an account? You can ignore this email.':'Heb je geen account aangemaakt? Dan kun je deze e-mail negeren.');
    }
}
