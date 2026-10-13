<?php
namespace App\Console\Commands;
use App\Models\User;
use App\Models\Article;
use App\Models\Comment;
use App\Services\Lightpoints;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class CreateDemoAccount extends Command {
 protected $signature='lichtpunt:demo-account {--reset-password : Maak een nieuw wachtwoord voor het bestaande demoaccount}';
 protected $description='Maak lokaal een demoaccount met voorbeeldpunten en privéreacties';
 public function handle(Lightpoints $points):int {
  if(!app()->environment('local','testing')){$this->error('Dit is uitsluitend bedoeld voor lokale Herd-tests.');return self::FAILURE;}
  $email='erik.demo@lichtpunt.test';$u=User::where('email',$email)->first();
  if($u&&$u->is_admin){$this->error('Dit adres is een beheeraccount. Er is niets gewijzigd.');return self::FAILURE;}
  $password=null;
  if(!$u){
   $password=Str::password(20);
   $u=DB::transaction(function()use($email,$password){
    $u=User::create(['name'=>'Erik Demo','email'=>$email,'password'=>$password]);
    $u->forceFill(['email_verified_at'=>now(),'community_accepted_at'=>now(),'community_version'=>'2026-10-10','invite_code'=>bin2hex(random_bytes(16))])->save();
    return $u;
   });
  }elseif($this->option('reset-password')){
   $password=Str::password(20);$u->forceFill(['password'=>$password,'remember_token'=>Str::random(60)])->save();
  }
  if(!$u->hasVerifiedEmail()||!$u->community_accepted_at||$u->is_suspended){$this->error('Het bestaande account is niet actief. Er is geen bevestiging of blokkade omzeild.');return self::FAILURE;}
  // Named demo references keep reruns idempotent. No public demo comments or AI calls.
  $points->award($u,'demo:welcome','demo',108,'Demo: eerder verzamelde lichtpunten');
  $points->award($u,'demo:read','demo',1,'Demo: een nieuwsverhaal gelezen');
  $points->award($u,'demo:share','demo',1,'Demo: een bericht klaargezet om te delen');
  $points->award($u,'demo:invite','demo',5,'Demo: een vriend bracht een eerste glimlach');
  $article=Article::where('status','published')->latest('published_at')->first();
  if($article){
   foreach([
    ['body'=>'Demoreactie: wat een stom verhaal.','status'=>'rejected','ai_reason'=>'Demokeuring: deze reactie moppert op het verhaal. Probeer een vriendelijke vraag of een positief aanknopingspunt.'],
    ['body'=>'Demoreactie: wat een bijzondere manier om het aan te pakken.','status'=>'doubt','ai_reason'=>'Demokeuring: de bedoeling is niet helemaal duidelijk; de beheerder kijkt mee.'],
   ] as $v){Comment::firstOrCreate(['user_id'=>$u->id,'article_id'=>$article->id,'body'=>$v['body']],['status'=>$v['status'],'ai_reason'=>$v['ai_reason'],'ai_confidence'=>.95]);}
  }else{$this->warn('Er is nog geen gepubliceerd artikel. De punten zijn klaar; voorbeeldreacties volgen als je deze opdracht na de nieuwsimport opnieuw uitvoert.');}
  $this->info('Lokaal demoaccount gereed.');$this->line('E-mail: '.$email);
  if($password)$this->line('Wachtwoord: '.$password);
  else $this->line('Gebruik je bestaande demowachtwoord, of voeg --reset-password toe.');
  $this->line('Inloggen: '.url('/inloggen'));$this->line('Profiel: '.url('/profiel'));
  $this->line('115 voorbeeldpunten; dit account heeft geen beheerrechten. Voorbeeldreacties zijn niet openbaar.');
  return self::SUCCESS;
 }
}
