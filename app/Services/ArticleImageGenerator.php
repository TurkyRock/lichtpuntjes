<?php
namespace App\Services;
use App\Models\Article;
class ArticleImageGenerator {
 private function generator():CloudflareIllustrator|ComfyUiImageGenerator {
  return match(config('news_images.provider','cloudflare')) {
   'cloudflare'=>app(CloudflareIllustrator::class),
   'comfyui'=>app(ComfyUiImageGenerator::class),
   default=>throw new NewsServiceException('Kies NEWS_IMAGE_PROVIDER=cloudflare of comfyui. De oude Pollinations-koppeling is verwijderd.'),
  };
 }
 public function acceptedModels():array {return [CloudflareIllustrator::MODEL,'black-forest-labs/flux.1-schnell',(string)config('news_images.checkpoint')];}
 public function recordCompleted(array $photo):void {}
 public function model():string {$this->generator();return config('news_images.provider','cloudflare')==='cloudflare'?CloudflareIllustrator::MODEL:(string)config('news_images.checkpoint');}
 public function check():void {$this->generator()->check();}
 public function summary():string {return config('news_images.provider','cloudflare')==='cloudflare'?'Cloudflare: account, token en FLUX.1 Schnell beschikbaar. Geen afbeelding gemaakt. Gebruik Workers Free; dagelijkse limieten worden door Cloudflare bewaakt.':'Lokale ComfyUI en checkpoint zijn beschikbaar. Er is niets gegenereerd.';}
 public function generate(Article $a):array {
  $style=app(LichtpuntIllustrationPrompt::class)->style($a);
  if(!in_array($style,[LichtpuntIllustrationPrompt::COLLAGE_STYLE,LichtpuntIllustrationPrompt::PHOTO_STYLE],true))return $this->generator()->generate($a);
  $editor=app(ArticleVisualBrief::class);$brief=$editor->plan($a);
  $prepared=clone $a;$prepared->photo=array_merge($a->photo??[],['visual_brief'=>$brief]);
  // At most two generated candidates: do not burn the image budget in an endless loop.
  for($attempt=0;$attempt<2;$attempt++){
   $photo=$this->generator()->generate($prepared);
   $url=$photo['url']??'';
   if(!preg_match('~^/news-images/[a-f0-9]{64}\.(jpg|png)$~',$url))throw new NewsServiceException('Beeldcontrole: ongeldig lokaal beeldpad.');
   $bytes=\Illuminate\Support\Facades\File::get(public_path(ltrim($url,'/')));
   $mime=@getimagesizefromstring($bytes)['mime']??null;
   if(!in_array($mime,['image/png','image/jpeg'],true))throw new NewsServiceException('Beeldcontrole: ongeldige afbeelding.');
   $review=$editor->review($a,$brief,$bytes,$mime);
   if($review['match']&&$review['confidence']>=.8&&($style===LichtpuntIllustrationPrompt::PHOTO_STYLE?($review['photorealistic']??false):($review['cheerful']&&$review['paper']))){
    return array_merge($photo,['visual_brief'=>$brief,'visual_review'=>$review+['model'=>config('news.gemini_model'),'checked_at'=>now()->toIso8601String()]]);
   }
   if($attempt===0&&(!$review['match']||$style===LichtpuntIllustrationPrompt::PHOTO_STYLE)){$brief['scene']=mb_substr('Close view of '.$brief['must_show'].'. '.$brief['scene'],0,500);$prepared->photo=array_merge($prepared->photo??[],['visual_brief'=>$brief]);}
  }
  throw new ArticleImageRejected('Beeldcontrole: twee kandidaten afgekeurd. Laatste reden: '.mb_substr(trim(strip_tags($review['reason']??'Onderwerp of beeldstijl onvoldoende herkenbaar.')),0,350));
 }
}
