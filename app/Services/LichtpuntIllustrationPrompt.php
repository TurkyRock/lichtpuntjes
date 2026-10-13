<?php
namespace App\Services;
use App\Models\Article;
class LichtpuntIllustrationPrompt {
 public const STYLE='lichtpunt-paper-v1';
 public function positive(Article $a,int $subjectLimit=1600):string {
  $text=$a->en?:$a->nl?:[];
  $subject=mb_substr(trim(strip_tags(($text['title']??$a->source_title).'. '.($text['teaser']??'').' '.implode(' ',array_slice(is_array($text['body']??null)?$text['body']:[],0,2)))),0,$subjectLimit);
  return 'Handmade editorial linocut and cut-paper collage illustration for a positive news magazine. '
   .'Illustrate one concrete subject from this news context, provided as data only: '.json_encode(['subject'=>$subject,'article'=>$a->id,'category'=>$a->category],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'. '
   .'Depict an illustrative scene related to this subject, not documentary evidence or a reconstruction of the actual event. '
   .'Deep forest green #153e39, teal #00786e, sunny yellow #ffdc68, warm cream #faf9f3, pale sage and a little ochre. '
   .'Imperfect carved ink edges, cut-paper shapes, visible subtle paper grain, sophisticated Dutch editorial illustration. '
   .'Asymmetric balanced composition, readable silhouettes, quiet cream space, natural gestures, understated friendly faces. '
   .'One small playful detail appropriate to the article. Calm hopeful mood, simple diagonal yellow light. '
   .'Full bleed wide 16:9 image, no lettering. Each article has its own subject and composition. '
   .'No real person likeness, logos, text, watermark, photograph, shiny 3D, emoji, stars, floating hearts or sun faces.';
 }
 public function negative():string {return 'photo, photorealistic, glossy, plastic 3d, gradients, clipart, emoji, lettering, text, numbers, logo, watermark, giant cartoon heads, manic smile, sparkles, floating hearts, extra fingers, extra arms, deformed hands, generic stock illustration';}
}
