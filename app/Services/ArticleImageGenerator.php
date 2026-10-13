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
 public function generate(Article $a):array {return $this->generator()->generate($a);}
}
