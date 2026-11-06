<?php
namespace App\Services;
use App\Models\Article;
use Illuminate\Support\Facades\{Cache,File,Http};
use Illuminate\Support\Sleep;
class CloudflareIllustrator {
 public const MODEL='@cf/black-forest-labs/flux-1-schnell';
 private function account():string {
  $id=trim((string)config('news_images.cloudflare_account_id'));
  if(!preg_match('/^[a-f0-9]{32}$/i',$id))throw new NewsServiceException('Cloudflare: CLOUDFLARE_ACCOUNT_ID ontbreekt of is ongeldig (32 hextekens).');
  return $id;
 }
 private function client(){
  $key=trim((string)config('news_images.cloudflare_token'));
  if($key==='')throw new NewsServiceException('Cloudflare: CLOUDFLARE_API_TOKEN ontbreekt. Maak een Workers AI-token met Read en Edit.');
  return Http::withToken($key)->acceptJson()->withOptions(['allow_redirects'=>false])->connectTimeout(10)->timeout(30);
 }
 private function url(string $path):string {return 'https://api.cloudflare.com/client/v4/accounts/'.$this->account().'/ai/'.$path;}
 private function successful($r):void {
  if($r->successful()&&$r->json('success')===true)return;
  $codes=array_map('strval',array_column(is_array($r->json('errors'))?$r->json('errors'):[],'code'));
  $reason=match($r->status()){
   401=>'API-token ongeldig of verlopen.',
   403=>'Toegang geweigerd. Controleer Account ID en Workers AI Read/Edit-rechten voor dit account.',
   402=>'Geen gratis capaciteit beschikbaar. Er wordt niet automatisch opgewaardeerd.',
   429=>'Daglimiet of snelheidslimiet bereikt. Probeer later opnieuw.',
   default=>in_array(1106,$codes)?'Daglimiet of capaciteit bereikt. Probeer later opnieuw.':'Aanvraag mislukt (HTTP '.$r->status().'). De batch stopt; hervat later met dezelfde opdracht.'
  };
  $quotaText=strtolower(json_encode($r->json('errors')));
  if(in_array('4006',$codes,true)&&(str_contains($quotaText,'daily free allocation')||str_contains($quotaText,'daily quota'))){
   app(DailyIllustrationBudget::class)->exhausted();
   throw new ImageBudgetException('AI-puntjes: het gratis Cloudflare-tegoed is vandaag op. We hervatten na de dagelijkse aanvulling.');
  }
  $details=[];
  foreach(array_slice(is_array($r->json('errors'))?$r->json('errors'):[],0,2) as $error){
   if(!is_array($error))continue;
   $code=is_numeric($error['code']??null)?(string)$error['code']:'onbekend';
   $message=is_string($error['message']??null)?$error['message']:'';
   // Show structured diagnostics, never raw response bodies or credentials.
   foreach(['cloudflare_token','cloudflare_account_id'] as $name){$secret=(string)config('news_images.'.$name);if($secret!=='')$message=str_replace($secret,'[verborgen]',$message);}
   $message=preg_replace('/Bearer\s+[^\s"\x27,}]+/i','Bearer [verborgen]',$message);
   $message=preg_replace('/[A-Za-z0-9_\-]{32,}/','[verborgen]',$message);
   $message=trim(preg_replace('/[\x00-\x1F\x7F]+/',' ',strip_tags($message)));
   $details[]='code '.$code.($message!==''?': '.mb_substr($message,0,350):'');
  }
  throw new NewsServiceException('Cloudflare: '.$reason.($details?' Details: '.implode(' | ',$details):''));
 }
 public function check():void {
  $url=$this->url('models/search');$client=$this->client();
  try{$r=$client->get($url,['search'=>'flux-1-schnell','per_page'=>100]);}catch(\Throwable $e){throw new NewsServiceException('Cloudflare: modelcontrole niet bereikbaar.');}
  $this->successful($r);$models=$r->json('result');
  foreach(is_array($models)?$models:[] as $m)if(is_array($m)&&($m['name']??'')===self::MODEL)return;
  throw new NewsServiceException('Cloudflare: FLUX.1 Schnell niet beschikbaar. Er wordt geen ander model gekozen.');
 }
 private function reserve():void {
  // Count every request before sending, including failed requests and retries.
  // Provider Free plan is the account-wide billing boundary; this is an extra local cap.
  $lock=Cache::lock('lichtpunt-cloudflare-request-counter',30);
  if(!$lock->get())throw new NewsServiceException('Cloudflare: een andere aanvraag werkt de dagteller bij.');
  try{
   $path=(string)config('news_images.cloudflare_state_file');$day=now('UTC')->format('Y-m-d');$state=['day'=>$day,'requests'=>0];
   if(is_file($path)){
    try{$saved=json_decode(File::get($path),true,512,JSON_THROW_ON_ERROR);}catch(\Throwable $e){throw new NewsServiceException('Cloudflare: dagteller is beschadigd. Geen aanvraag verstuurd.');}
    if(!is_array($saved)||!is_string($saved['day']??null)||!is_int($saved['requests']??null)||$saved['requests']<0)throw new NewsServiceException('Cloudflare: dagteller is ongeldig. Geen aanvraag verstuurd.');
    if($saved['day']===$day)$state=$saved;
   }
   $max=(int)config('news_cycle.cloudflare_attempts',50);
   if($state['requests']>=$max)throw new ImageBudgetException('AI-puntjes: Cloudflare heeft de lokale limiet van '.$max.' aanvragen per UTC-dag bereikt. We hervatten morgen.');
   $state['requests']++;File::ensureDirectoryExists(dirname($path));$tmp=$path.'.tmp';
   if(File::put($tmp,json_encode($state,JSON_THROW_ON_ERROR))===false||!rename($tmp,$path))throw new NewsServiceException('Cloudflare: dagteller opslaan mislukt. Geen aanvraag verstuurd.');
  }finally{$lock->release();}
 }
 private function retryDelay($response,int $attempt):?int {
  if($attempt>=3)return null;
  $errors=$response->json('errors');$codes=array_map('strval',array_column(is_array($errors)?$errors:[],'code'));
  $quotaText=strtolower(json_encode($errors));
  if(in_array('4006',$codes,true)&&(str_contains($quotaText,'daily free allocation')||str_contains($quotaText,'daily quota')))return null;
  // Do not retry known exhausted account quotas or permanent model/input errors.
  if(array_intersect($codes,['3036','1106','5035','5007','5004','3003','3042']))return null;
  $status=$response->status();if(!in_array($status,[409,429],true)&&($status<500||$status>599))return null;
  $delay=[20,40,60][$attempt];$after=$response->header('Retry-After');
  if(is_string($after)&&$after!==''){
   $seconds=ctype_digit($after)?(int)$after:max(0,(int)(strtotime($after)-time()));
   if($seconds>60)return null; // Leave long cooldowns to the next manual resume.
   $delay=max($delay,$seconds);
  }
  return $delay;
 }
 private function pause(int $seconds,string $message):void {
  if(app()->runningInConsole()&&!app()->runningUnitTests())fwrite(STDOUT,$message.PHP_EOL);
  Sleep::for($seconds)->seconds();
 }
 /** One small-moment request; share the existing account-wide request cap. */
 public function generateSmallMoment(string $prompt):string {
  if(strlen($prompt)>2048)throw new NewsServiceException('Momentjesprompt is te lang.');
  $url=$this->url('run/'.self::MODEL);$client=$this->client()->asJson();
  $this->pause(10,'Cloudflare: korte pauze voor een momentjesafbeelding.');
  $this->reserve();
  try{$r=$client->timeout(90)->post($url,['prompt'=>$prompt,'steps'=>4]);}
  catch(\Throwable $e){throw new NewsServiceException('Momentjesafbeelding tijdelijk niet bereikbaar.');}
  $this->successful($r);$encoded=$r->json('result.image');
  $bytes=is_string($encoded)&&strlen($encoded)<=16*1024*1024?base64_decode($encoded,true):false;
  if(!is_string($bytes)||strlen($bytes)>12*1024*1024)throw new NewsServiceException('Geen bruikbare momentjesafbeelding ontvangen.');
  return $bytes;
 }
 public function generate(Article $a):array {
  $url=$this->url('run/'.self::MODEL);$client=$this->client()->asJson();$prompt=app(LichtpuntIllustrationPrompt::class);
  $context=$prompt->positive($a,450);
  for($limit=400;strlen($context)>2048&&$limit>=0;$limit-=50)$context=$prompt->positive($a,$limit);
  if(strlen($context)>2048)throw new NewsServiceException('Cloudflare: illustratieprompt is te lang. Geen aanvraag verstuurd.');
  $this->pause(10,'Cloudflare: 10 seconden pauze voor de volgende illustratie.');
  // One initial attempt plus at most three retries of explicit temporary responses.
  for($attempt=0;$attempt<4;$attempt++){
   $this->reserve();
   try{$r=$client->timeout(120)->post($url,['prompt'=>$context,'steps'=>4]);}catch(\Throwable $e){throw new NewsServiceException('Cloudflare: generatieverbinding mislukt. Aanvraag kan al uitgevoerd zijn; hervat na controle van je gebruik.');}
   if($r->successful()&&$r->json('success')===true)break;
   $delay=$this->retryDelay($r,$attempt);if($delay===null)break;
   $this->pause($delay,'Cloudflare: tijdelijke fout (HTTP '.$r->status().'); nieuwe poging '.($attempt+1).'/3 over '.$delay.' seconden.');
  }
  $this->successful($r);$encoded=$r->json('result.image');
  $bytes=is_string($encoded)&&strlen($encoded)<=16*1024*1024?base64_decode($encoded,true):false;
  $info=is_string($bytes)&&strlen($bytes)<=12*1024*1024?@getimagesizefromstring($bytes):false;
  $ext=match($info['mime']??''){'image/jpeg'=>'jpg','image/png'=>'png',default=>null};
  if(!$ext||($info[0]??0)<512||($info[1]??0)<288)throw new NewsServiceException('Cloudflare gaf geen bruikbare JPEG/PNG-illustratie. Bestaande afbeelding blijft staan.');
  $hash=hash('sha256',$bytes);
  if(Article::where('id','!=',$a->id)->where('photo->sha256',$hash)->exists())throw new NewsServiceException('Deze illustratie wordt al gebruikt. Bestaande afbeelding blijft staan.');
  foreach(Article::where('id','!=',$a->id)->whereNotNull('photo')->cursor() as $other){$url=$other->photo['url']??'';if(preg_match('~^/news-images/[a-f0-9]{64}\.(jpg|png)$~',$url)&&is_file(public_path(ltrim($url,'/')))&&hash_file('sha256',public_path(ltrim($url,'/')))===$hash)throw new NewsServiceException('Deze illustratie wordt al gebruikt. Bestaande afbeelding blijft staan.');}
  File::ensureDirectoryExists(public_path('news-images'));$file=public_path('news-images/'.$hash.'.'.$ext);$tmp=$file.'.'.\Illuminate\Support\Str::uuid().'.tmp';
  try{if(!is_file($file)&&(File::put($tmp,$bytes)===false||!rename($tmp,$file)))throw new NewsServiceException('Illustratie opslaan mislukt. Controleer schrijfrechten.');}finally{if(is_file($tmp))File::delete($tmp);}
  return ['url'=>'/news-images/'.$hash.'.'.$ext,'sha256'=>$hash,'generated'=>true,'illustrative'=>true,'creator'=>'AI / Cloudflare','model'=>self::MODEL,'style'=>$prompt->style($a),'seed'=>null,'subject_hash'=>hash('sha256',$prompt->positive($a)),'description'=>($prompt->style($a)===LichtpuntIllustrationPrompt::PHOTO_STYLE?'AI-gegenereerd sfeerbeeld bij: ':'Lichtpuntjes-illustratie bij: ').($a->nl['title']??$a->source_title),'created_at'=>now()->toIso8601String(),'provider'=>'cloudflare'];
 }
}
