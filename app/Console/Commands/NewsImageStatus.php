<?php
namespace App\Console\Commands;

use App\Models\{Article,LaptopImageJob};
use Illuminate\Console\Command;

class NewsImageStatus extends Command
{
 protected $signature='news:image-status {articles?* : Optioneel: artikelnummer(s)}';
 protected $description='Toon de beeldwachtrij per soort bijdrage en beeldpogingen, zonder teksten of sleutels.';
 public function handle():int
 {
  $ids=$this->argument('articles');
  foreach($ids as $id)if(!ctype_digit((string)$id)){$this->error('Gebruik alleen artikelnummers.');return self::INVALID;}
  $query=LaptopImageJob::query();
  if($ids)$query->where('target_type','article')->whereIn('target_id',$ids);
  $latest=(clone $query)->selectRaw('MAX(id)')->groupBy('target_type','target_id');
  $current=(clone $query)->whereIn('id',$latest)->get(['id','target_type','target_id','status']);
  $origins=Article::whereIn('id',$current->where('target_type','article')->pluck('target_id'))->pluck('origin','id');
  $kind=fn($job)=>$job->target_type==='moment'?'Momentje':(($origins[$job->target_id]??null)==='community'?'Zelfgeschreven artikel':'Nieuwsartikel');
  $rows=[];
  foreach(['Nieuwsartikel','Momentje','Zelfgeschreven artikel'] as $label){
   $group=$current->filter(fn($j)=>$kind($j)===$label);
   $rows[]=[$label,$group->where('status','queued')->count(),$group->where('status','processing')->count(),$group->where('status','generated')->count(),$group->whereIn('status',['failed','rejected'])->count()];
  }
  $this->table(['Bijdrage','Wachtend','Bezig','Klaar voor controle','Mislukt / afgekeurd'],$rows);
  $this->info('Nog te maken: '.$current->whereIn('status',['queued','processing'])->count().' beeldopdrachten.');
  $this->line('Per bijdrage telt alleen de laatste beeldpoging. Tekstopdrachten voor beeldvoorbereiding zijn hierin niet meegeteld.');
  $jobs=$query->latest('id')->limit(20)->get();
  if($jobs->isEmpty()){$this->info('Geen beeldopdrachten gevonden.');return self::SUCCESS;}
  foreach($jobs as $job){
   // User-written contribution details stay private; only imported news has editorial reasons here.
   $review=$job->target_type==='article'&&($origins[$job->target_id]??null)==='news'?($job->result['visual_review']??[]):[];
   $this->line('#'.$job->target_id.' | '.$kind($job).' | poging '.$job->attempt.($job->target_type==='article'?'/'.\App\Services\LaptopArticleImages::MAX_ATTEMPTS:'').' | '.$job->status.' | '.($job->failure_code??'-'));
   if(isset($review['reason'])){
    $reason=mb_substr(preg_replace('/[\x00-\x1F\x7F]+/u',' ',strip_tags((string)$review['reason'])),0,500);
    $this->line('  Beeldcontrole: '.\Symfony\Component\Console\Formatter\OutputFormatter::escape($reason));
   }elseif($job->status==='rejected')$this->line('  Oude afkeuring zonder opgeslagen reden; wordt bij herkansing opnieuw bekeken.');
  }
  return self::SUCCESS;
 }
}
