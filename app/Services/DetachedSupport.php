<?php
namespace App\Services;
use App\Models\SupportPledge;
use Illuminate\Support\Facades\{Mail,URL};
class DetachedSupport {
 public static function emailHash(string $email):string{return hash_hmac('sha256',mb_strtolower(trim($email)),(string)config('app.key'));}
 public function sendLink(SupportPledge $p,string $email):void {
  if(in_array(config('mail.default'),['log','array'],true)&&!app()->environment('testing'))throw new \RuntimeException('Stel eerst werkende e-mail in. Zonder beheerlink kan je maandelijkse steun niet buiten je account worden beheerd.');
  $url=URL::temporarySignedRoute('support.detached',now()->addDays(7),['pledge'=>$p->id]);
  $message=\App\Support\AccountMail::message(false,'contribution')
   ->subject('Beheer je maandelijkse steun aan Jouw Lichtpuntjes')
   ->greeting('Jouw steun, jouw keuze.')
   ->line('Je maandelijkse steun aan Jouw Lichtpuntjes bedraagt € '.number_format($p->cents/100,2,',','.').'. Dank je wel dat je deze plek helpt stralen.')
   ->line('Via je persoonlijke link kun je je steun bekijken of stoppen, ook zonder account.')
   ->action('Beheer mijn maandelijkse steun',$url)
   ->line('Deze persoonlijke link is 7 dagen geldig. Deel hem niet met anderen.')
   ->line('Later een nieuwe link nodig? Vraag die aan via '.url('/steun/beheren').'. Vragen: '.config('privacy.email'));
  Mail::send($message->view,$message->data(),fn($m)=>$m->to($email)->subject($message->subject));
 }
}
