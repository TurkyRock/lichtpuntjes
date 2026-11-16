<?php
namespace App\Services;

use App\Models\{Article,LaptopImageJob};
use Illuminate\Support\Facades\{File,Log};

class LaptopArticleImages
{
 public const MAX_ATTEMPTS = 3;

 private function review(Article $article, LaptopImageJob $job):array
 {
  $photo=$job->result;
  $bytes=File::get(public_path(ltrim($photo['url'],'/')));
  $mime=@getimagesizefromstring($bytes)['mime']??null;
  if(!in_array($mime,['image/jpeg','image/png'],true))throw new NewsServiceException('De gegenereerde afbeelding is niet leesbaar.');
  return app(ArticleVisualBrief::class)->review($article,$job->payload['brief'],$bytes,$mime);
 }

 private function payload(Article $article,array $brief,array $base=[]):array
 {
  $prepared=clone $article;
  $prepared->photo=array_merge($article->photo??[],['visual_brief'=>$brief]);
  $prompt=app(LichtpuntIllustrationPrompt::class);
  $positive=$prompt->positive($prepared);
  return array_merge($base,['positive'=>$positive,'negative'=>$prompt->negative($prepared),
   'width'=>1024,'height'=>576,'seed'=>random_int(1,2147483646),
   'model'=>(string)config('news_images.checkpoint'),'style'=>$prompt->style($article),
   'subject_hash'=>hash('sha256',$positive),'brief'=>$brief]);
 }

 public function generate(Article $a):array
 {
  $queue=app(LaptopImages::class);$hash=$queue->hashArticle($a);$style=app(LichtpuntIllustrationPrompt::class)->style($a);
  $job=LaptopImageJob::where('target_type','article')->where('target_id',$a->id)->where('content_hash',$hash)->latest('attempt')->first();
  if($job&&$job->status==='failed')throw new NewsServiceException('De beeldopdracht wacht op een technische controle.');
  if($job&&in_array($job->status,['completed','applied']))return $job->result;
  if($job&&$job->status==='generated'){
   $review=$this->review($a,$job);
   $photo=array_merge($job->result,['visual_review'=>$review+['model'=>config('local-ai.enabled')?config('local-ai.vision_model'):config('news.gemini_model'),'checked_at'=>now()->toIso8601String()]]);
   if($review['match']&&$review['confidence']>=.8&&($style===LichtpuntIllustrationPrompt::PHOTO_STYLE?($review['photorealistic']??false):($review['cheerful']&&$review['paper']))){
    $photo['visual_brief']=$job->payload['brief'];
    $job->update(['status'=>'completed','result'=>$photo]);return $photo;
   }
   // Persist the verdict before asynchronous replanning, including the reason.
   $job->update(['status'=>'rejected','failure_code'=>'visual_review','result'=>$photo]);
   Log::info('Article image review rejected',['article_id'=>$a->id,'attempt'=>$job->attempt,'match'=>$review['match'],'confidence'=>$review['confidence']]);
  }
  if($job&&$job->status==='rejected'){
   if($job->attempt>=self::MAX_ATTEMPTS)throw new ArticleImageRejected('Drie afbeeldingen afgekeurd. Bewaard voor handmatige controle; de uurronde zoekt een ander artikel.');
   // Historical rejected jobs did not save their reason. Recheck that image once.
   $review=$job->result['visual_review']??null;
   if(!is_array($review)){
    $review=$this->review($a,$job);
    $job->update(['result'=>array_merge($job->result,['visual_review'=>$review])]);
   }
   $brief=app(ArticleVisualBrief::class)->revise($a,$job->payload['brief'],$review);
   $payload=$this->payload($a,$brief,$job->payload);
   $payload['retry_feedback']=['previous_attempt'=>$job->attempt,'review'=>$review];
   $queue->enqueue('article',$a->id,$hash,$payload,$job->attempt+1);
   throw new ImagePendingException('Verbeterde beeldopdracht staat klaar: poging '.($job->attempt+1).' van '.self::MAX_ATTEMPTS.'.');
  }
  if($job&&$job->status==='cancelled')throw new NewsServiceException('De beeldopdracht is geannuleerd; controleer de artikelstatus.');
  if(!$job){
   $brief=app(ArticleVisualBrief::class)->plan($a);
   $queue->enqueue('article',$a->id,$hash,$this->payload($a,$brief));
  }
  $attempt=$job?->attempt??1;
  throw new ImagePendingException('Afbeelding staat in de laptopwachtrij: poging '.$attempt.' van '.self::MAX_ATTEMPTS.'.');
 }
}
