<?php
namespace App\Console\Commands;
use App\Models\Article;
use App\Services\{DailyStoryScore,DailySpotlight};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;
class ScoreDailyCandidates extends Command {
 protected $signature='news:spotlight-score {--limit=20 : Maximaal 1 tot 100 bestaande verhalen beoordelen}';
 protected $description='Geef bestaande recente goedgekeurde verhalen een AI-dagverhaalscore';
 public function handle(DailyStoryScore $scores,DailySpotlight $daily):int {
  $raw=(string)$this->option('limit');if(!ctype_digit($raw)||(int)$raw<1||(int)$raw>100){$this->error('--limit moet tussen 1 en 100 liggen.');return self::FAILURE;}
  $lock=Cache::lock('lichtpunt-daily-score',7200);if(!$lock->get()){$this->warn('Er loopt al een dagverhaalbeoordeling.');return self::SUCCESS;}
  try {$candidates=Article::visible()->whereNull('daily_scored_at')->where('daily_score_attempts','<',3)->whereBetween('published_at',[now()->subDays(7),now()])->orderByDesc('published_at')->limit((int)$raw)->get();
   foreach($candidates as $i=>$a){if($i)Sleep::for(8)->seconds();try{$scores->assess($a);$this->line('#'.$a->id.' dagverhaalscore '.$a->fresh()->daily_score.'/100');}catch(\Throwable $e){$this->error('Scorecontrole gestopt: AI tijdelijk niet beschikbaar. Publicaties blijven behouden.');return self::FAILURE;}}
   $daily->choose();$this->info($candidates->count().' bestaande verhalen beoordeeld.');return self::SUCCESS;
  }finally{$lock->release();}
 }
}
