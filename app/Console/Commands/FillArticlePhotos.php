<?php
namespace App\Console\Commands;
use App\Models\Article;
use App\Services\ArticlePhotoFinder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
class FillArticlePhotos extends Command {
    protected $signature='news:photos {--limit=5 : Maximum aantal berichten} {--publish-drafts : Publiceer bestaande complete concepten} {--retry-now : Negeer de wachttijd na een fotofout}';
    protected $description='Vul ontbrekende afbeeldingen aan met lokale Lichtpuntjes-illustraties';
    public function handle(ArticlePhotoFinder $photos): int {
        $raw=(string)$this->option('limit');
        if (!ctype_digit($raw)||(int)$raw<1||(int)$raw>50) {
            $this->error('--limit moet tussen 1 en 50 liggen.'); return self::FAILURE;
        }
        $lock=Cache::lock('lichtpunt-photos',7500);
        if (!$lock->get()) { $this->warn('Er loopt al een foto-opdracht.'); return self::SUCCESS; }
        try {
            if ($this->option('publish-drafts')) {
                foreach (Article::where('status','draft')->cursor() as $a) {
                    $complete=true;
                    foreach (['nl','en'] as $language) {
                        $text=$a->$language;
                        if (!is_array($text)||empty($text['title'])||empty($text['teaser'])||empty($text['body'])||!is_array($text['body'])) $complete=false;
                    }
                    if ($complete) { $a->update(['status'=>'published']); $this->line('#'.$a->id.' gepubliceerd'); }
                }
            }
            $query=Article::whereIn('status',['draft','published'])->whereNull('photo');
            if (!$this->option('retry-now')) $query->where(fn($q)=>$q->whereNull('photo_next_attempt_at')->orWhere('photo_next_attempt_at','<=',now()));
            $articles=$query->orderBy('photo_next_attempt_at')->orderBy('id')->limit((int)$raw)->get();
            if ($articles->isEmpty()) $this->info('Geen ontbrekende afbeeldingen die nu aan de beurt zijn.');
            $failed=false;
            foreach ($articles as $a) {
                try {
                    $photos->attach($a);
                    $this->line('#'.$a->id.' '.(!empty($a->photo['generated'])?'AI-afbeelding':'foto').' toegevoegd');
                } catch (\Throwable $e) {
                    $failed=true;
                    $message=$e instanceof \App\Services\NewsServiceException?$e->getMessage():'Fototaak mislukt. Controleer verbinding, database en schrijfrechten.';
                    $a->update(['photo_next_attempt_at'=>now()->addMinutes(15)]);
                    $this->warn('#'.$a->id.' '.$message);
                    break; // Stop bij lokale model-/servicefout; nieuwe poging met --retry-now.
                }
            }
            return $failed?self::FAILURE:self::SUCCESS;
        } finally { $lock->release(); }
    }
}
