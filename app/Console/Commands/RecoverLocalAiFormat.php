<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\{Article,LocalAiJob};
class RecoverLocalAiFormat extends Command {
 protected $signature='local-ai:recover-format {--apply : Zet alleen technisch mislukte controles opnieuw klaar}';
 protected $description='Hervat technisch mislukte lokale AI-controles na herstel van de JSON-ontvangst';
 public function handle():int {
  $jobs=LocalAiJob::where('status','failed')->where('expires_at','>',now());
  $articles=Article::where('origin','news')->whereNull('hidden_at')->where('status','error')->where('reason','like','Lokale AI leverde na de beschikbare pogingen geen geldig antwoord.%');
  $this->line('Technisch mislukte, niet-verlopen AI-opdrachten: '.$jobs->count());$this->line('Technisch overgeslagen nieuwsartikelen: '.$articles->count());
  if(!$this->option('apply')){$this->info('Voorbeeldcontrole. Voeg --apply toe om ze opnieuw klaar te zetten.');return self::SUCCESS;}
  $jobs->update(['status'=>'queued','claims'=>0,'result'=>null,'completion_hash'=>null,'lease_hash'=>null,'lease_until'=>null,'available_at'=>now()]);
  $articles->update(['status'=>'pending','attempts'=>0,'reason'=>null]);
  $this->info('Controles opnieuw klaargezet. Er is niets automatisch goedgekeurd of gepubliceerd.');return self::SUCCESS;
 }
}
