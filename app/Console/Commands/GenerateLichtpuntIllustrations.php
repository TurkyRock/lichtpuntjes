<?php
namespace App\Console\Commands;
use App\Models\Article;
use App\Services\{ArticleImageGenerator,ArticlePhotoFinder,NewsServiceException};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache,File};
class GenerateLichtpuntIllustrations extends Command {
 protected $signature='news:illustrations {--collage : Maak tactiele knipselcollages met papierlagen} {--photorealistic : Vervang oude schetsen door fotorealistische AI-sfeerbeelden} {--all : Alle gepubliceerde nieuwsberichten} {--limit=10 : Maximum aantal nieuwe illustraties} {--article= : Alleen dit artikel-ID} {--force : Ook bestaande illustraties in deze stijl opnieuw maken} {--check : Alleen verbinding en model controleren}';
 protected $description='Maak unieke Lichtpuntjes-illustraties met Cloudflare of lokale ComfyUI';
 public function handle(ArticleImageGenerator $generator,ArticlePhotoFinder $finder):int {
  $limit=(string)$this->option('limit');$id=(string)$this->option('article');
  if($this->option('collage')&&$this->option('photorealistic')){$this->error('Kies --collage of --photorealistic.');return self::FAILURE;}
  $target=$this->option('collage')?\App\Services\LichtpuntIllustrationPrompt::COLLAGE_STYLE:($this->option('photorealistic')?\App\Services\LichtpuntIllustrationPrompt::PHOTO_STYLE:null);
  if(!ctype_digit($limit)||(int)$limit<1||(int)$limit>500||($id!==''&&(!ctype_digit($id)||(int)$id<1))){$this->error('--limit: 1–500; --article: een positief artikel-ID.');return self::FAILURE;}
  $lock=Cache::lock('lichtpunt-illustration-batch',86400);if(!$lock->get()){$this->error('Er loopt al een illustratiebatch.');return self::FAILURE;}
  try {
   try{$generator->check();}catch(NewsServiceException $e){$this->error($e->getMessage());return self::FAILURE;}
   if($this->option('check')){$this->info($generator->summary());return self::SUCCESS;}
   File::ensureDirectoryExists(storage_path('app'));
   $reportPath='app/illustrations-'.now()->format('Ymd-His').'-'.substr((string)\Illuminate\Support\Str::uuid(),0,8).'.csv';$report=fopen(storage_path($reportPath),'w');if(!$report)throw new \RuntimeException('Rapport maken mislukt.');
   fputcsv($report,['artikel','titel','status','opmerking']);$made=0;$skipped=0;$failed=0;$attempts=0;$streak=0;
   $query=Article::where('status','published')->whereNull('hidden_at')->where('origin','news')->where('id','<=',Article::max('id')??0);if($id!=='')$query->where('id',(int)$id);
   foreach($query->lazyById(25) as $a){
    if($target)$a->photo=array_merge($a->photo??[],['requested_style'=>$target]);
    if(!$this->option('force')&&$finder->current($a)){$skipped++;continue;}
    if(!$this->option('all')&&$attempts>=(int)$limit)break;$attempts++;
    $this->line('#'.$a->id.' illustratie maken: '.($a->nl['title']??$a->source_title));
    try{if($target)$a->save();$finder->attach($a,false,true,(bool)$this->option('force'));$made++;$streak=0;$this->info('#'.$a->id.' klaar');fputcsv($report,[$a->id,$a->source_title,'gemaakt',$a->fresh()->photo['url']]);}
    catch(\Throwable $e){$failed++;$streak++;$reason=$e instanceof NewsServiceException?$e->getMessage():'Generatie of opslag mislukt; controleer de Laravel-logboeken en schrijfrechten.';$this->warn('#'.$a->id.' '.$reason);fputcsv($report,[$a->id,$a->source_title,'fout',$reason]);if(str_contains($reason,'Cloudflare')||str_contains($reason,'ComfyUI')||str_contains($reason,'wachttijd')||$streak>=3){$this->warn('Gestopt. Bestaande afbeeldingen blijven staan; hervat met dezelfde opdracht.');break;}}
   }
   $this->info($made.' gemaakt, '.$skipped.' al klaar, '.$failed.' mislukt. Rapport: storage/'.$reportPath);return $failed?self::FAILURE:self::SUCCESS;
  }finally{if(isset($report)&&is_resource($report))fclose($report);$lock->release();}
 }
}
