<?php
namespace App\Services;
use App\Models\{Article,LaptopImageJob};
use Illuminate\Support\Facades\File;
class LaptopArticleImages {
 public function generate(Article $a):array {
  $queue=app(LaptopImages::class);$hash=$queue->hashArticle($a);$style=app(LichtpuntIllustrationPrompt::class)->style($a);
  $job=LaptopImageJob::where('target_type','article')->where('target_id',$a->id)->where('content_hash',$hash)->latest('attempt')->first();
  if($job&&$job->status==='failed')throw new NewsServiceException('De beeldopdracht wacht op een technische controle.');
  if($job&&in_array($job->status,['completed','applied']))return $job->result;
  if($job&&$job->status==='generated'){
   $payload=$job->payload;$photo=$job->result;$bytes=File::get(public_path(ltrim($photo['url'],'/')));
   $review=app(ArticleVisualBrief::class)->review($a,$payload['brief'],$bytes,'image/jpeg');
   if($review['match']&&$review['confidence']>=.8&&($style===LichtpuntIllustrationPrompt::PHOTO_STYLE?($review['photorealistic']??false):($review['cheerful']&&$review['paper']))){
    $photo+=['visual_brief'=>$payload['brief'],'visual_review'=>$review+['model'=>config('news.gemini_model'),'checked_at'=>now()->toIso8601String()]];
    $job->update(['status'=>'completed','result'=>$photo]);return $photo;
   }
   $job->update(['status'=>'rejected','failure_code'=>'visual_review']);
  }
  if($job&&in_array($job->status,['rejected','cancelled'])){
   if($job->attempt>=2)throw new ArticleImageRejected('Twee afbeeldingen passen onvoldoende bij dit verhaal. Een beheerder kijkt mee.');
   $payload=$job->payload;$payload['seed']=random_int(1,2147483646);$queue->enqueue('article',$a->id,$hash,$payload,$job->attempt+1);
  }elseif(!$job){
   $brief=app(ArticleVisualBrief::class)->plan($a);$prepared=clone $a;$prepared->photo=array_merge($a->photo??[],['visual_brief'=>$brief]);$prompt=app(LichtpuntIllustrationPrompt::class);
   $queue->enqueue('article',$a->id,$hash,['positive'=>$prompt->positive($prepared),'negative'=>$prompt->negative($prepared),'width'=>1024,'height'=>576,'seed'=>random_int(1,2147483646),'model'=>(string)config('news_images.checkpoint'),'style'=>$style,'subject_hash'=>hash('sha256',$prompt->positive($prepared)),'brief'=>$brief]);
  }
  throw new ImagePendingException('Je afbeelding staat in de laptopwachtrij.');
 }
}
