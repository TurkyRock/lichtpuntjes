<?php
namespace App\Services;
use App\Models\Article;
use Illuminate\Support\Facades\{Cache,File};
class ArticlePhotoFinder {
 public function current(Article $a):bool {
  $p=$a->photo??[];$url=$p['url']??'';
  return !empty($p['generated'])&&in_array($p['style']??'',[LichtpuntIllustrationPrompt::STYLE,LichtpuntIllustrationPrompt::PHOTO_STYLE],true)&&($p['style']??'')===app(LichtpuntIllustrationPrompt::class)->style($a)&&in_array($p['model']??'',app(ArticleImageGenerator::class)->acceptedModels(),true)&&($p['subject_hash']??'')===hash('sha256',app(LichtpuntIllustrationPrompt::class)->positive($a))&&preg_match('~^/news-images/[a-f0-9]{64}\.(jpg|png)$~',$url)&&is_file(public_path(ltrim($url,'/')))&&hash_file('sha256',public_path(ltrim($url,'/')))===($p['sha256']??'');
 }
 public function attach(Article $article,bool $realOnly=false,bool $replaceAi=false,bool $force=false):bool {
  if($realOnly)throw new NewsServiceException('Externe fotozoekdiensten zijn verwijderd. Gebruik news:illustrations.');
  $lock=Cache::lock('lichtpunt-photo-assignment',7200);if(!$lock->get())throw new NewsServiceException('Er loopt al een illustratiegeneratie.');
  try {
   $article->refresh();if(!$force&&$this->current($article))return true;
   $photo=app(ArticleImageGenerator::class)->generate($article);
   if($article->photo){File::ensureDirectoryExists(storage_path('app/illustration-backups'));$backup=['article_id'=>$article->id,'photo'=>$article->photo,'saved_at'=>now()->toIso8601String()];if(File::put(storage_path('app/illustration-backups/'.$article->id.'-'.\Illuminate\Support\Str::uuid().'.json'),json_encode($backup,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))===false)throw new NewsServiceException('Vorige fotogegevens bewaren mislukt; afbeelding nog niet vervangen.');}
   \Illuminate\Support\Facades\DB::transaction(function()use($article,$photo){$article->update(['photo'=>$photo,'photo_next_attempt_at'=>null]);app(ArticleImageGenerator::class)->recordCompleted($photo);});return true;
  }finally{$lock->release();}
 }
}
