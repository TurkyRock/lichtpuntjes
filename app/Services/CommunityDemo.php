<?php
namespace App\Services;

use App\Models\{User,Article,Comment,PositiveMoment,ProfilePhoto,LightspotSubmission};
use Illuminate\Support\Facades\{DB,Storage,File};
use Illuminate\Support\Str;
use RuntimeException;

/** Explicit local fixtures, never a path around the ordinary moderation flow. */
class CommunityDemo
{
 private const VERSION='demo-2026-10-17';
 public static function people():array {
  return [
   'sanne'=>['name'=>'Sanne (voorbeeld)','moment'=>'De buurvrouw zag dat ik haast had en zette spontaan een kop koffie voor me klaar. Zo’n klein gebaar, zo’n grote glimlach.','title'=>'[Voorbeeld] Een kop koffie die mijn ochtend veranderde','story'=>'Ik rende van afspraak naar afspraak en was vooral bezig met wat er nog moest. Mijn buurvrouw stond bij haar deur en vroeg of ik even wilde zitten. Twee minuten later zaten we met een kop koffie in de zon. Er hoefde niets opgelost te worden. Even luisteren en samen lachen was genoeg om de rest van de ochtend anders te beginnen.','en_title'=>'[Example] A coffee that changed my morning','en_story'=>'I was rushing between appointments when my neighbour invited me to sit down. A cup of coffee in the sunshine and a little conversation helped me start the rest of the morning with a smile.'],
   'youssef'=>['name'=>'Youssef (voorbeeld)','moment'=>'Tijdens mijn lunchwandeling vroeg een oudere meneer of ik hem wilde helpen een bankje te verplaatsen. Het werd een klein avontuur: samen sjouwen, even uitrusten en vervolgens een heel gezellig gesprek over zijn oude moestuin. Ik kwam tien minuten later terug dan gepland, maar met een veel beter humeur. Soms is even tijd maken voor een ander precies het pauzemoment dat je zelf nodig had.','title'=>'[Voorbeeld] Het bankje dat voor een gesprek zorgde','story'=>'Mijn vaste lunchrondje was deze keer net iets anders. Iemand vroeg of ik kon helpen een bankje uit de wind te zetten. We tilden het een stukje op en gingen daarna zelf even zitten. Hij vertelde over zijn oude moestuin en ik over mijn balkonplanten. Tien minuten later liep ik verder met een nieuwe tip voor mijn tomatenplant en het fijne gevoel dat ik iemand had leren kennen.','en_title'=>'[Example] A bench that started a conversation','en_story'=>'On my lunch walk I helped someone move a bench out of the wind. We sat down afterwards and talked about gardens and balcony plants. I left with a new tip for my tomato plant and the lovely feeling of having met someone.'],
   'marieke'=>['name'=>'Marieke (voorbeeld)','moment'=>'Mijn kleinzoon belde alleen maar om te vertellen dat hij aan me dacht. Geen reden, geen vraag. Gewoon even samen lachen.','title'=>'[Voorbeeld] Een telefoontje zonder reden','story'=>'De telefoon ging toen ik net de planten water gaf. Mijn kleinzoon had geen ingewikkelde vraag en er was ook niets aan de hand. Hij wilde vertellen over een grappig moment op school. We hebben allebei zo hard gelachen dat ik vergat welke plant al water had gekregen. Soms is een gewoon telefoontje het mooiste cadeau van de week.','en_title'=>'[Example] A phone call for no special reason','en_story'=>'My grandson called while I was watering the plants. Nothing was wrong and he needed no help: he simply wanted to share a funny moment from school. We laughed so much that I forgot which plant I had already watered.'],
   'tom'=>['name'=>'Tom (voorbeeld)','moment'=>'Vandaag mijn eerste rondje hardgelopen zonder op de klok te kijken. Onderweg drie honden begroet. Dat telt ook als training, toch?','title'=>'[Voorbeeld] Hardlopen op het tempo van een glimlach','story'=>'Ik ging naar buiten met het plan om snel een rondje te lopen. Na de eerste bocht besloot ik de sportapp dicht te doen. Ik keek naar de bomen, stopte even bij een hond en groette iemand die ik elke ochtend tegenkom. Het rondje werd niet sneller, maar wel een stuk gezelliger. Dit tempo wil ik vaker proberen.','en_title'=>'[Example] Running at the pace of a smile','en_story'=>'I set off for a quick run, then decided to close the sports app. I watched the trees, stopped to greet a dog and said hello to a familiar face. The run was not faster, but it was much more enjoyable.'],
  ];
 }
 private function guard():void {if(!app()->environment('local','testing'))throw new RuntimeException('Voorbeeldgebruikers zijn uitsluitend bedoeld voor lokaal testen in Herd.');}
 private function email(string $key):string{return $key.'@demo.jouwlichtpuntje.invalid';}
 private function owned(User $user,array $person):bool{return !$user->is_admin&&$user->community_version===self::VERSION&&$user->name===$person['name'];}
 private function preflight(bool $assets=true):void {
  $this->guard();
  foreach(self::people() as $key=>$p){
   $u=User::where('email',$this->email($key))->first();
   if($u&&!$this->owned($u,$p))throw new RuntimeException('Dit voorbeeldadres is al in gebruik door een ander account. Niets gewijzigd.');
   if($assets&&!is_file(database_path('demo-portraits/'.$key.'.jpg')))throw new RuntimeException('Portret ontbreekt: database/demo-portraits/'.$key.'.jpg. Pak het hele updatepakket uit.');
   if($assets&&$u&&$u->is_suspended)throw new RuntimeException('Een voorbeeldaccount is geschorst. De blokkade is niet omzeild.');
  }
 }
 public function install(bool $resetPasswords=false):array {
  $this->preflight();
  return DB::transaction(function()use($resetPasswords){
   $users=[];$stories=[];$moments=[];$credentials=[];
   foreach(self::people() as $key=>$person){
    $u=User::where('email',$this->email($key))->lockForUpdate()->first();
    $password=null;
    if(!$u){$password=Str::password(22);$u=User::create(['name'=>$person['name'],'email'=>$this->email($key),'password'=>$password]);$u->forceFill(['email_verified_at'=>now(),'community_accepted_at'=>now(),'community_version'=>self::VERSION,'invite_code'=>bin2hex(random_bytes(16)),'is_admin'=>false,'is_suspended'=>false])->save();}
    elseif($resetPasswords){$password=Str::password(22);$u->forceFill(['password'=>$password,'remember_token'=>Str::random(60)])->save();}
    if($password)$credentials[]=['name'=>$u->name,'email'=>$u->email,'password'=>$password];
    $bytes=File::get(database_path('demo-portraits/'.$key.'.jpg'));
    $path='profile-photos/demo-community/'.$key.'.jpg';
    if(!Storage::disk('local')->put($path,$bytes))throw new RuntimeException('Voorbeeldportret opslaan mislukt.');
    $photo=ProfilePhoto::firstOrCreate(['user_id'=>$u->id,'ai_model'=>'demo-community-portrait'],['path'=>$path,'status'=>'approved','reason'=>'Fictief voorbeeldprofiel met AI-portret, expliciet aangemaakt als lokale testdata. Geen echte identiteit of AI-keuring geclaimd.','self_confirmed_at'=>now()]);
    if($photo->status!=='approved')throw new RuntimeException('Een voorbeeldportret is intussen gewijzigd of verwijderd. Installeer het niet stilzwijgend opnieuw.');
    $u->forceFill(['profile_photo_id'=>$photo->id])->save();
    $hash=hash('sha256',$bytes);File::ensureDirectoryExists(public_path('news-images'));
    if(File::put(public_path('news-images/'.$hash.'.jpg'),$bytes)===false)throw new RuntimeException('Voorbeeldafbeelding opslaan mislukt.');
    $body='Fictief voorbeeldverhaal van een fictieve gebruiker. Deze bijdrage is uitsluitend lokale testinhoud. '.$person['story'];
    $enBody='Fictional sample story from a fictional user, provided solely as local test content. '.$person['en_story'];
    $a=Article::firstOrCreate(['fingerprint'=>hash('sha256','demo-community:v1:'.$key)],['origin'=>'community','author_id'=>$u->id,'status'=>'published','source_title'=>$person['title'],'source_name'=>$u->name,'source_url'=>url('/momentjes'),'category'=>'people','published_at'=>now()->subHours(4),'daily_score'=>0,'nl'=>['title'=>$person['title'],'teaser'=>Str::limit($person['story'],160),'body'=>[$body]],'en'=>['title'=>$person['en_title'],'teaser'=>Str::limit($person['en_story'],160),'body'=>[$enBody]],'photo'=>['url'=>'/news-images/'.$hash.'.jpg','generated'=>true,'style'=>LichtpuntIllustrationPrompt::PHOTO_STYLE,'model'=>'demo-portrait','sha256'=>$hash,'description'=>'AI-portret van de fictieve voorbeeldauteur; geen echte persoon of gebeurtenis.']]);
    if($a->author_id!==$u->id)throw new RuntimeException('Een voorbeeldartikel heeft niet de verwachte eigenaar.');
    LightspotSubmission::firstOrCreate(['article_id'=>$a->id],['user_id'=>$u->id,'title'=>$person['title'],'body'=>$body,'language'=>'nl','category'=>'people','status'=>'published','reason'=>['nl'=>'Lokale fictieve testinhoud, geen echte AI-keuring.','en'=>'Local fictional test content, not an actual AI review.'],'translations'=>['nl'=>$a->nl,'en'=>$a->en],'confidence'=>null,'moderator_note'=>'Voorbeeldverhaal.']);
    $m=PositiveMoment::firstOrCreate(['user_id'=>$u->id,'ai_model'=>'demo-community:public:'.$key],['body'=>$person['moment'],'visibility'=>'public','status'=>'approved','reason'=>['nl'=>'Fictief lokaal voorbeeldmoment, geen echte AI-keuring.','en'=>'Fictional local example, not an actual AI review.'],'moment_date'=>now('Europe/Amsterdam')->toDateString(),'ai_accepted_at'=>now()]);
    $users[$key]=$u;$stories[$key]=$a;$moments[$key]=$m;
   }
   $articles=Article::visible()->orderByDesc('published_at')->orderByDesc('id')->limit(6)->get();
   $keys=array_keys($users);
   foreach($keys as $index=>$key){
    $u=$users[$key];$visitor=hash('sha256','demo-community-visitor:'.$key);
    // Read history and counters are created together, once per visitor/day/story.
    for($day=0;$day<$index+3;$day++)foreach($articles->take(2+$index%2) as $a){
     $date=now('Europe/Amsterdam')->subDays($day)->toDateString();
     if(DB::table('article_reads')->insertOrIgnore(['article_id'=>$a->id,'visitor_hash'=>$visitor,'read_date'=>$date]))Article::whereKey($a->id)->increment('views_count');
     $this->point($u,'read:'.$date.':'.$a->id,'read',1,'Voorbeeld: een lichtpuntje gelezen',$date);
    }
    foreach($articles->take($index+2) as $a)if(DB::table('user_article_likes')->insertOrIgnore(['article_id'=>$a->id,'user_id'=>$u->id,'created_at'=>now()->subMinutes($index+5)]))Article::whereKey($a->id)->increment('likes_count');
    $target=$stories[$keys[($index+1)%4]];
    foreach(['Voorbeeldreactie: wat een lief gebaar. Hier word ik blij van!','Voorbeeldreactie: dank voor het delen, ik neem deze glimlach mee mijn dag in.'] as $text){
     $c=Comment::firstOrCreate(['user_id'=>$u->id,'article_id'=>$target->id,'body'=>$text],['status'=>'approved','ai_reason'=>'Fictieve lokale voorbeeldreactie.','ai_model'=>'demo-fixture','approved_at'=>now()->subMinutes(10)]);
     $this->point($u,'comment:'.$c->id,'comment',1,'Voorbeeld: een vriendelijke reactie',now('Europe/Amsterdam')->toDateString());
    }
    for($day=1;$day<=$index+2;$day++){
     $date=now('Europe/Amsterdam')->subDays($day*2)->toDateString();
     $private=PositiveMoment::firstOrCreate(['user_id'=>$u->id,'ai_model'=>'demo-community:calendar:'.$day],['body'=>'Privévoorbeeld: even rustig buiten gezeten en genoten van een klein moment voor mezelf.','visibility'=>'private','status'=>'approved','reason'=>['nl'=>'Fictief kalenderitem.','en'=>'Fictional calendar entry.'],'moment_date'=>$date,'ai_accepted_at'=>now()]);
     $date=$private->moment_date->toDateString();$this->point($u,'moment:'.$date,'moment',1,'Voorbeeld: een momentje om te bewaren',$date);
    }
    $date=$moments[$key]->moment_date->toDateString();$this->point($u,'moment:'.$date,'moment',1,'Voorbeeld: een momentje om te bewaren',$date);
    // Only the three other fictional users can give a sample heart.
    for($j=1;$j<=1+$index%3;$j++)DB::table('moment_hearts')->insertOrIgnore(['moment_id'=>$moments[$key]->id,'user_id'=>$users[$keys[($index+$j)%4]]->id,'created_at'=>now()]);
   }
   return ['users'=>count($users),'moments'=>count($moments),'stories'=>count($stories),'credentials'=>$credentials];
  });
 }
 private function point(User $u,string $reference,string $kind,int $points,string $label,string $date):void {
  DB::table('lightpoint_events')->insertOrIgnore(['user_id'=>$u->id,'reference'=>$reference,'kind'=>$kind,'points'=>$points,'label'=>$label,'period'=>$date,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
 }
 public function remove():int {
  $this->preflight(false);$paths=[];$images=[];
  $count=DB::transaction(function()use(&$paths,&$images){
   $count=0;
   foreach(self::people() as $key=>$p){
    $u=User::where('email',$this->email($key))->lockForUpdate()->first();if(!$u)continue;
    foreach(ProfilePhoto::where('user_id',$u->id)->get() as $photo)if($photo->path&&str_starts_with($photo->path,'profile-photos/demo-community/'))$paths[]=$photo->path;
    foreach(DB::table('user_article_likes')->where('user_id',$u->id)->select('article_id')->get() as $like)$this->decrement((int)$like->article_id,'likes_count',1);
    $reads=DB::table('article_reads')->where('visitor_hash',hash('sha256','demo-community-visitor:'.$key));
    foreach((clone $reads)->selectRaw('article_id, count(*) as total')->groupBy('article_id')->get() as $read)$this->decrement((int)$read->article_id,'views_count',(int)$read->total);
    $reads->delete();
    foreach(Article::where('author_id',$u->id)->whereIn('fingerprint',array_map(fn($k)=>hash('sha256','demo-community:v1:'.$k),array_keys(self::people())))->get() as $a){$images[]=$a->photo['url']??'';$a->delete();}
    $u->delete();$count++;
   }
   return $count;
  });
  foreach($paths as $path)Storage::disk('local')->delete($path);
  foreach(array_unique($images) as $url)if(preg_match('~^/news-images/[a-f0-9]{64}\.jpg$~',$url)&&!Article::where('photo->url',$url)->exists())File::delete(public_path(ltrim($url,'/')));
  return $count;
 }
 private function decrement(int $id,string $column,int $count):void {$a=Article::whereKey($id)->lockForUpdate()->first();if($a)$a->update([$column=>max(0,(int)$a->{$column}-$count)]);}
}
