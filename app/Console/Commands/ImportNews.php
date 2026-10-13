<?php
namespace App\Console\Commands;
use App\Models\Article;
use App\Services\NewsImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
class ImportNews extends Command {
    protected $signature='news:import {--process-only : Verwerk opgeslagen berichten} {--pages=1 : Aantal nieuwspagina\'s, 1 tot 50} {--limit= : Maximum te beoordelen berichten, 1 tot 500} {--skip-photos : Laat afbeeldingen door news:photos aanvullen}';
    protected $description='Importeer wereldwijd nieuws en publiceer geschikte NL/EN-berichten';
    private function safeMessage(\Throwable $e): string {
        $message=$e->getMessage();
        // Alleen onze zelfgemaakte provider-/validatiefouten tonen, nooit ruwe request-exceptions.
        if (get_class($e)===\RuntimeException::class && preg_match('/^(NewsData |Gemini |AI-formaatfout: |Vertaling ontbreekt\.|NEWSDATA_API_KEY ontbreekt\.|GEMINI_API_KEY ontbreekt\.|Ongeldige modelnaam\.)/',$message)) return $message;
        return 'Verwerking mislukt. Controleer database en configuratie.';
    }
    public function handle(NewsImporter $importer): int {
        $rawPages=(string)$this->option('pages');
        if (!ctype_digit($rawPages)||(int)$rawPages<1||(int)$rawPages>50) {
            $this->error('--pages moet tussen 1 en 50 liggen.'); return self::FAILURE;
        }
        $rawLimit=$this->option('limit');
        if ($rawLimit!==null&&(!ctype_digit((string)$rawLimit)||(int)$rawLimit<1||(int)$rawLimit>500)) {
            $this->error('--limit moet tussen 1 en 500 liggen.'); return self::FAILURE;
        }
        $limit=$rawLimit===null?max(1,min(20,(int)config('news.batch_size'))):(int)$rawLimit;
        // Langere batches houden hun lock tot ruim na hun maximale verwerkingstijd.
        $secondsPerArticle=$this->option('skip-photos')?100:500;
        $lock=Cache::lock('lichtpunt-import',max(7500,$limit*$secondsPerArticle+(int)$rawPages*50+600));
        if (!$lock->get()) { $this->warn('Er loopt al een import.'); return self::SUCCESS; }
        try {
            if (!$this->option('process-only')) $this->info($importer->fetch((int)$rawPages).' nieuwe berichten opgeslagen.');
            $articles=Article::whereIn('status',['pending','error'])->where('attempts','<',3)->orderBy('id')->limit($limit)->get();
            if ($articles->isEmpty()) {
                $this->info('Geen berichten meer om te verwerken. Haal nieuwe op met news:import --pages=5.');
            }
            $exhausted=Article::where('status','error')->where('attempts','>=',3)->count();
            if ($exhausted>0) $this->warn($exhausted.' foutbericht(en) na drie pogingen overgeslagen; bekijk deze met news:review.');
            $failed=false;
            $totals=['published'=>0,'rejected'=>0,'insufficient'=>0];
            $processed=0;
            foreach ($articles as $i=>$a) {
                if ($i>0) usleep(8000000); // Beperkt bursts; projectquota blijven bepalend.
                try {
                    $importer->assess($a);
                    $fresh=$a->fresh();
                    $processed++;
                    if (isset($totals[$fresh->status])) $totals[$fresh->status]++;
                    $this->line('#'.$a->id.' '.$fresh->status);
                    if (!$this->option('skip-photos') && $fresh->status==='published' && empty($fresh->photo)) {
                        try {
                            if (!app(\App\Services\ArticlePhotoFinder::class)->attach($fresh)) $this->warn('#'.$a->id.' geen passende foto gevonden.');
                        } catch (\Throwable $photoError) {
                            $fresh->update(['photo_next_attempt_at'=>now()->addMinutes(15)]);
                            $message=$photoError instanceof \App\Services\NewsServiceException?$photoError->getMessage():'Illustratietaak mislukt. Probeer news:illustrations.';
                            $this->warn('#'.$a->id.' '.$message);
                        }
                    }
                }
                catch (\Throwable $e) {
                    $failed=true;
                    $a->update(['status'=>'error','reason'=>$this->safeMessage($e)]);
                    $this->error('#'.$a->id.' niet verwerkt: '.$this->safeMessage($e));
                    break; // Stop bij providerfout om quota en herhaalde fouten te beperken.
                }
            }
            $remaining=Article::whereIn('status',['pending','error'])->where('attempts','<',3)->count();
            $this->info('Batch: '.$processed.' beoordeeld, '.$totals['published'].' gepubliceerd, '.$totals['rejected'].' afgewezen, '.$totals['insufficient'].' onvoldoende broninformatie.');
            if ($remaining>0) $this->info($remaining.' berichten wachten nog. Verder: news:import --process-only --limit=200');
            if ($this->option('skip-photos')&&$totals['published']>0) $this->info('Afbeeldingen aanvullen: news:photos --limit=50 --retry-now');
            return $failed?self::FAILURE:self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Import mislukt: '.$this->safeMessage($e));
            return self::FAILURE;
        } finally { $lock->release(); }
    }
}
