<?php
namespace App\Services;
use App\Models\SupportPledge;
use Illuminate\Support\Facades\{Mail,URL};
class DetachedSupport {
 public static function emailHash(string $email):string{return hash_hmac('sha256',mb_strtolower(trim($email)),(string)config('app.key'));}
 public function sendLink(SupportPledge $p,string $email):void {
  if(in_array(config('mail.default'),['log','array'],true)&&!app()->environment('testing'))throw new \RuntimeException('Stel eerst werkende e-mail in. Zonder beheerlink kan je maandelijkse steun niet buiten je account worden beheerd.');
  $url=URL::temporarySignedRoute('support.detached',now()->addDays(7),['pledge'=>$p->id]);
  Mail::raw("Je maandelijkse steun aan Jouw Lichtpuntjes bedraagt €".number_format($p->cents/100,2,',','.').".\nBeheer of stop deze via onderstaande persoonlijke link (7 dagen geldig):\n".$url."\n\nVraag later een nieuwe link aan via ".url('/steun/beheren').". Deel deze link niet. Vragen: ".config('privacy.email'),fn($m)=>$m->to($email)->subject('Beheer je maandelijkse steun aan Jouw Lichtpuntjes'));
 }
}
