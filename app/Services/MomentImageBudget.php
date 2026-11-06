<?php
namespace App\Services;
use Illuminate\Support\Facades\{Cache, File};

class MomentImageBudget
{
    public function reserve(): void
    {
        if (Cache::get('lichtpunt-cloudflare-exhausted')) throw new ImageBudgetException('Afbeeldingen wachten op nieuw dagtegoed.');
        Cache::lock('moment-image-daily-budget', 10)->block(2, function () {
            $path = (string) config('moment-images.budget_file');
            $day = now('UTC')->toDateString();
            $state = ['day' => $day, 'attempts' => 0];
            if (is_file($path)) {
                $saved = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($saved) || !is_string($saved['day'] ?? null) || !is_int($saved['attempts'] ?? null) || $saved['attempts'] < 0) throw new \RuntimeException('Ongeldige momentjesteller.');
                if ($saved['day'] === $day) $state = $saved;
            }
            if ($state['attempts'] >= max(0, (int) config('moment-images.daily_limit', 6))) throw new ImageBudgetException('De dagruimte voor momentjesafbeeldingen is op.');
            ++$state['attempts'];
            File::ensureDirectoryExists(dirname($path));
            $tmp = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
            try {
                if (File::put($tmp, json_encode($state, JSON_THROW_ON_ERROR)) === false || !rename($tmp, $path)) throw new \RuntimeException('Momentjesteller opslaan mislukt.');
            } finally { if (is_file($tmp)) File::delete($tmp); }
        });
    }
}
