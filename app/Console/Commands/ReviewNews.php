<?php
namespace App\Console\Commands;
use App\Models\Article;
use Illuminate\Console\Command;
class ReviewNews extends Command {
    protected $signature='news:review {id? : Artikel-ID} {--publish : Publiceer dit gecontroleerde concept} {--reject : Verberg het bericht}';
    protected $description='Bekijk concepten en publiceer of verberg een afzonderlijk bericht';
    public function handle(): int {
        if ($this->option('publish') && $this->option('reject')) { $this->error('Kies één actie.'); return self::FAILURE; }
        $id=$this->argument('id');
        if (!$id) {
            if ($this->option('publish')||$this->option('reject')) { $this->error('Een artikel-ID is verplicht.'); return self::FAILURE; }
            $this->table(['ID','Status','Titel'],Article::orderByDesc('id')->limit(30)->get()->map(fn($a)=>[$a->id,$a->status,$a->nl['title']??$a->source_title])->all());
            return self::SUCCESS;
        }
        $a=Article::find($id);
        if (!$a) { $this->error('Artikel niet gevonden.'); return self::FAILURE; }
        if ($this->option('publish')) {
            if ($a->status!=='draft'||empty($a->nl['body'])||empty($a->en['body'])) { $this->error('Alleen complete concepten kunnen worden gepubliceerd.'); return self::FAILURE; }
            $a->update(['status'=>'published']); $this->info('Bericht gepubliceerd.');
        } elseif ($this->option('reject')) { $a->update(['status'=>'rejected']); $this->info('Bericht verborgen.'); }
        else {
            $this->line($a->source_name.' — '.$a->source_url);
            $this->line('Status: '.$a->status.' | '.$a->reason);
            $this->line('Bron: '.$a->source_title."\n".$a->source_description);
            foreach (['nl','en'] as $l) { $this->line(strtoupper($l).': '.($a->$l['title']??'')); foreach (($a->$l['body']??[]) as $p) $this->line($p); }
        }
        return self::SUCCESS;
    }
}
