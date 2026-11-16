<?php
namespace App\Console\Commands;

use App\Models\{Article,LaptopImageJob};
use Illuminate\Console\Command;

class NewsImageStatus extends Command
{
 protected $signature='news:image-status {articles?* : Optioneel: artikelnummer(s)}';
 protected $description='Toon beeldpogingen en afkeurreden voor geïmporteerd nieuws, zonder sleutels of privébijdragen.';
 public function handle():int
 {
  $ids=$this->argument('articles');
  foreach($ids as $id)if(!ctype_digit((string)$id)){$this->error('Gebruik alleen artikelnummers.');return self::INVALID;}
  $query=LaptopImageJob::where('target_type','article')->whereIn('target_id',Article::where('origin','news')->select('id'));
  if($ids)$query->whereIn('target_id',$ids);
  $jobs=$query->latest('id')->limit(20)->get();
  if($jobs->isEmpty()){$this->info('Geen beeldopdrachten gevonden.');return self::SUCCESS;}
  foreach($jobs as $job){
   $review=$job->result['visual_review']??[];
   $this->line('#'.$job->target_id.' | poging '.$job->attempt.'/'.\App\Services\LaptopArticleImages::MAX_ATTEMPTS.' | '.$job->status.' | '.($job->failure_code??'-'));
   if(isset($review['reason'])){
    $reason=mb_substr(preg_replace('/[\x00-\x1F\x7F]+/u',' ',strip_tags((string)$review['reason'])),0,500);
    $this->line('  Beeldcontrole: '.\Symfony\Component\Console\Formatter\OutputFormatter::escape($reason));
   }elseif($job->status==='rejected')$this->line('  Oude afkeuring zonder opgeslagen reden; wordt bij herkansing opnieuw bekeken.');
  }
  return self::SUCCESS;
 }
}
