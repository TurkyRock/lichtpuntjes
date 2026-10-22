<?php
namespace App\Console\Commands;

use App\Services\NewsDuplicates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckNewsDuplicates extends Command
{
    protected $signature = 'news:duplicates {--apply : Verberg de gevonden dubbelen}';
    protected $description = 'Controleer nieuwstitels over alle bronnen en beide leestalen';

    public function handle(NewsDuplicates $duplicates): int
    {
        $rows = Cache::lock('lichtpunt-news-title-import', 120)->block(10,
            fn () => $duplicates->clean((bool)$this->option('apply')));
        if (!$rows) { $this->info('Geen dubbele nieuwstitels gevonden.'); return self::SUCCESS; }
        $this->table(['Behouden ID', 'Dubbel ID', 'Titel'], array_map(fn ($r) => array_values($r), $rows));
        $this->info(count($rows).($this->option('apply') ? ' dubbele berichten verborgen. Bijdragen en interacties blijven bewaard.' : ' dubbelen gevonden. Gebruik --apply om ze te verbergen.'));
        return self::SUCCESS;
    }
}
