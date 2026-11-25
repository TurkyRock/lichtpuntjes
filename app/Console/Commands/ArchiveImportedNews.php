<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ArchiveImportedNews extends Command
{
    protected $signature = 'news:archive-import {--apply : Verberg de huidige import en begin een nieuwe uurronde}';
    protected $description = 'Maak ruimte voor een verse NewsData-import met behoud van bestaande artikelen';

    public function handle(): int
    {
        $articles = Article::where('origin', 'news')->whereNull('hidden_at');
        if (!$this->option('apply')) {
            $this->info($articles->count().' geïmporteerde nieuwsartikelen en kandidaten worden verborgen.');
            return self::SUCCESS;
        }

        if (config('news-feeds.enabled')) {
            $this->error('Schakel de aanvullende nieuwsfeeds eerst uit.');
            return self::FAILURE;
        }

        $lock = Cache::lock('lichtpunt-import', 120);
        if (!$lock->get()) {
            $this->error('Er draait nog een import. Probeer opnieuw zodra die klaar is.');
            return self::FAILURE;
        }

        try {
            $path = (string) config('news_hourly.state_file');
            if (is_file($path) && !rename($path, $path.'.archived-'.\Illuminate\Support\Str::uuid())) {
                $this->error('De uurronde kon niet worden gearchiveerd.');
                return self::FAILURE;
            }

            $count = $articles->update(['hidden_at' => now()]);
            $this->info($count.' geïmporteerde artikelen en kandidaten verborgen. De volgende geplande uurronde begint opnieuw.');
            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
