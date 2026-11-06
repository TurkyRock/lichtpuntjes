<?php
namespace App\Services;
use App\Models\BetaParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class BetaTests {
 public const TASKS=[
 'first'=>['title'=>'Een eerste glimlach','text'=>'Open de homepage. Kijk rustig rond zonder in te loggen. Waar denk je dat deze website voor bedoeld is? Voel je je uitgenodigd om verder te kijken?'],
 'account'=>['title'=>'Maak het jouw plekje','text'=>'Maak een gratis account en bevestig je e-mailadres. Probeer daarna in en uit te loggen. Gebruik geen wachtwoord dat je ook ergens anders gebruikt.'],
 'article'=>['title'=>'Een verhaal dat je raakt','text'=>'Zoek een artikel dat je aanspreekt. Lees het, geef een hartje en bekijk hoe je het kunt delen. Vergelijk eventueel ook de Engelse versie. Je hoeft het niet echt naar iemand te sturen.'],
 'conversation'=>['title'=>'Geef een glimlach door','text'=>'Plaats een vriendelijke reactie bij een artikel. Antwoord ook op een bestaande reactie als die er is. Kun je zien welke antwoorden bij elkaar horen?'],
 'moment'=>['title'=>'Een klein gelukje','text'=>'Bewaar een klein positief moment van vandaag. Bekijk de privacykeuze en deel alleen iets wat je echt wilt delen. Kijk later of er een passende kleine afbeelding bij staat.'],
 'story'=>['title'=>'Jouw eigen lichtpuntje','text'=>'Schrijf een kort positief verhaal. Bekijk wat er na het insturen gebeurt en of duidelijk is dat AI het controleert en een afbeelding maakt. Goedkeuring en beeld kunnen even duren.'],
 'profile'=>['title'=>'Alles op zijn plek','text'=>'Zoek je accountoverzicht en Mijn gegevens. Probeer een gebruikersnaam aan te passen en bekijk waar je reacties, momentjes en badges terugvindt.'],
 'support'=>['title'=>'Een steuntje','text'=>'Bekijk de steunpagina. Is duidelijk waarom er donaties zijn en dat de site gratis blijft? Je hoeft niets te betalen.'],
 'summary'=>['title'=>'Jouw terugblik','text'=>'Je hebt Jouw Lichtpuntje nu leren kennen. Wat neem je mee? Met jouw eerlijke terugblik help je mij de website fijner te maken. Ook kritische opmerkingen zijn welkom.']
 ];
 public function participant(Request $r):?BetaParticipant {
  $id=$r->session()->get('beta.participant');$version=$r->session()->get('beta.version');
  if(!is_string($id)&&is_string($r->cookie('beta_access'))){$parts=explode('.',$r->cookie('beta_access'));$id=$parts[0]??null;$version=isset($parts[1])?(int)$parts[1]:null;}
  if(!is_string($id))return null;
  $p=BetaParticipant::find($id);
  return $p&&!$p->revoked_at&&$p->expires_at->isFuture()&&$p->access_version===$version?$p:null;
 }
 public function invite(string $label):array {
  return DB::transaction(function()use($label){
   DB::table('beta_rounds')->where('id',1)->lockForUpdate()->first();
   abort_if(BetaParticipant::count()>=(int)config('beta.limit',3),422,'Deze ronde heeft al drie testpersonen.');
   $token=bin2hex(random_bytes(24));$p=BetaParticipant::create(['id'=>(string)Str::uuid(),'label'=>$label,'token_hash'=>hash('sha256',$token),'expires_at'=>now()->addDays((int)config('beta.days',14))]);
   return [$p->refresh(),$token];
  });
 }
 public static function csvCell(mixed $value):string {
  $s=(string)($value??'');return preg_match('/^[\s\x00-\x1F]*[=+@\-]/u',$s)?"'".$s:$s;
 }
}
