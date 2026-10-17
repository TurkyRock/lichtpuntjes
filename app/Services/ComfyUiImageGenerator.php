<?php
namespace App\Services;
use App\Models\Article;
use Illuminate\Support\Facades\{File,Http};
class ComfyUiImageGenerator {
 private function base():string {
  $base=rtrim((string)config('news_images.comfy_url'),' /');$p=parse_url($base);
  if(($p['scheme']??'')!=='http'||!in_array($p['host']??'',['127.0.0.1','localhost'],true)||isset($p['user'])||isset($p['pass'])||!empty($p['path'])||isset($p['query'])||isset($p['fragment']))throw new NewsServiceException('COMFYUI_URL moet een lokaal HTTP-adres zijn, bijvoorbeeld http://127.0.0.1:8188.');
  return $base;
 }
 private function client(){return Http::withOptions(['allow_redirects'=>false,'proxy'=>''])->connectTimeout(5)->timeout(30);}
 public function check():void {
  $base=$this->base();
  try{$r=$this->client()->get($base.'/object_info/CheckpointLoaderSimple');}catch(\Throwable $e){throw new NewsServiceException('ComfyUI is niet bereikbaar. Start de lokale ComfyUI-server.');}
  if(!$r->successful())throw new NewsServiceException('ComfyUI-modelcontrole mislukt (HTTP '.$r->status().').');
  $models=$r->json('CheckpointLoaderSimple.input.required.ckpt_name.0',[]);
  if(!is_array($models)||!in_array(config('news_images.checkpoint'),$models,true))throw new NewsServiceException('COMFYUI_CHECKPOINT ontbreekt in ComfyUI/models/checkpoints. Controleer de bestandsnaam.');
 }
 public function generate(Article $article):array {
  $base=$this->base();$prompt=app(LichtpuntIllustrationPrompt::class);$seed=random_int(1,2147483646);
  $graph=[
   '3'=>['class_type'=>'KSampler','inputs'=>['seed'=>$seed,'steps'=>28,'cfg'=>6,'sampler_name'=>'dpmpp_2m','scheduler'=>'karras','denoise'=>1,'model'=>['4',0],'positive'=>['6',0],'negative'=>['7',0],'latent_image'=>['5',0]]],
   '4'=>['class_type'=>'CheckpointLoaderSimple','inputs'=>['ckpt_name'=>config('news_images.checkpoint')]],
   '5'=>['class_type'=>'EmptyLatentImage','inputs'=>['width'=>1024,'height'=>576,'batch_size'=>1]],
   '6'=>['class_type'=>'CLIPTextEncode','inputs'=>['text'=>$prompt->positive($article),'clip'=>['4',1]]],
   '7'=>['class_type'=>'CLIPTextEncode','inputs'=>['text'=>$prompt->negative($article),'clip'=>['4',1]]],
   '8'=>['class_type'=>'VAEDecode','inputs'=>['samples'=>['3',0],'vae'=>['4',2]]],
   '9'=>['class_type'=>'SaveImage','inputs'=>['filename_prefix'=>'lichtpunt-'.$article->id.'-'.$seed,'images'=>['8',0]]],
  ];
  try{$r=$this->client()->post($base.'/prompt',['prompt'=>$graph,'client_id'=>(string)\Illuminate\Support\Str::uuid()]);}
  catch(\Throwable $e){throw new NewsServiceException('ComfyUI-opdracht versturen mislukt.');}
  $id=$r->json('prompt_id');if(!$r->successful()||!is_string($id)||!preg_match('/^[a-zA-Z0-9-]+$/',$id))throw new NewsServiceException('ComfyUI heeft de workflow afgewezen. Controleer model en standaardnodes.');
  $until=microtime(true)+max(30,min(3600,(int)config('news_images.timeout',900)));$image=null;
  do {
   try{$history=$this->client()->get($base.'/history/'.$id);}
   catch(\Throwable $e){throw new NewsServiceException('Verbinding met ComfyUI verbroken. Bestaande afbeelding blijft staan.');}
   if(!$history->successful())throw new NewsServiceException('ComfyUI-voortgang niet beschikbaar.');
   $job=$history->json($id);
   if(is_array($job)){
    if(($job['status']['status_str']??'')==='error')throw new NewsServiceException('ComfyUI-generatie mislukt. Bekijk ComfyUI voor model- of videogeheugenproblemen.');
    foreach($job['outputs']['9']['images']??[] as $i)if(($i['type']??'')==='output'){$image=$i;break;}
    if($image)break;
    if(($job['status']['completed']??false))throw new NewsServiceException('ComfyUI heeft geen afbeelding opgeleverd.');
   }
   if(microtime(true)>=$until)break;
   usleep(max(1,min(10,(int)config('news_images.poll_seconds',2)))*1000000);
  }while(microtime(true)<$until);
  if(!$image)throw new NewsServiceException('ComfyUI-wachttijd verlopen. Bekijk de wachtrij voor je opnieuw probeert; de oude afbeelding blijft staan.');
  try{$r=$this->client()->get($base.'/view',['filename'=>$image['filename'],'subfolder'=>$image['subfolder']??'','type'=>'output']);}
  catch(\Throwable $e){throw new NewsServiceException('Lokale illustratie ophalen mislukt.');}
  if(!$r->successful())throw new NewsServiceException('Lokale illustratie ophalen mislukt (HTTP '.$r->status().').');
  $bytes=$r->body();if(strlen($bytes)>12*1024*1024)throw new NewsServiceException('Illustratie is te groot.');
  $info=@getimagesizefromstring($bytes);$ext=match($info['mime']??''){'image/jpeg'=>'jpg','image/png'=>'png',default=>null};
  if(!$ext||($info[0]??0)<512||($info[1]??0)<288)throw new NewsServiceException('Geen geldige JPEG/PNG-illustratie ontvangen.');
  $hash=hash('sha256',$bytes);
  if(Article::where('id','!=',$article->id)->where('photo->sha256',$hash)->exists())throw new NewsServiceException('Deze illustratie wordt al door een ander artikel gebruikt. Probeer opnieuw voor een unieke afbeelding.');
  foreach(Article::where('id','!=',$article->id)->whereNotNull('photo')->cursor() as $other){$url=$other->photo['url']??'';if(preg_match('~^/news-images/[a-f0-9]{64}\.(jpg|png)$~',$url)&&is_file(public_path(ltrim($url,'/')))&&hash_file('sha256',public_path(ltrim($url,'/')))===$hash)throw new NewsServiceException('Deze illustratie wordt al gebruikt. Probeer opnieuw.');}
  File::ensureDirectoryExists(public_path('news-images'));$name=$hash.'.'.$ext;
  $tmp=public_path('news-images/'.$hash.'.'.\Illuminate\Support\Str::uuid().'.tmp');
  try {if(!is_file(public_path('news-images/'.$name))&&(File::put($tmp,$bytes)===false||!rename($tmp,public_path('news-images/'.$name))))throw new NewsServiceException('Illustratie opslaan mislukt. Controleer schrijfrechten.');}finally{if(is_file($tmp))File::delete($tmp);}
  return ['url'=>'/news-images/'.$name,'sha256'=>$hash,'generated'=>true,'illustrative'=>true,'creator'=>'AI / lokale ComfyUI','model'=>config('news_images.checkpoint'),'style'=>$prompt->style($article),'seed'=>$seed,'subject_hash'=>hash('sha256',$prompt->positive($article)),'description'=>($prompt->style($article)===LichtpuntIllustrationPrompt::PHOTO_STYLE?'AI-gegenereerd sfeerbeeld bij: ':'Lichtpuntjes-illustratie bij: ').($article->nl['title']??$article->source_title),'created_at'=>now()->toIso8601String()];
 }
}
