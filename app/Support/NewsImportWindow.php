<?php
namespace App\Support;

use Carbon\CarbonInterface;

final class NewsImportWindow
{
    public const PAUSED = 'Nieuwsimport heeft nachtrust (22:00–06:00, Europe/Amsterdam). Voortgang blijft bewaard; openstaande laptopbeelden kunnen doorgaan.';

    public static function isOpen(?CarbonInterface $at = null): bool
    {
        $hour = ($at ?? now())->copy()->timezone('Europe/Amsterdam')->hour;
        return $hour >= 6 && $hour < 22;
    }
}
