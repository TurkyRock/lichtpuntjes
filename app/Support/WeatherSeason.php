<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class WeatherSeason
{
    public const LABELS = [
        'spring' => ['nl' => 'Lente', 'en' => 'Spring'],
        'summer' => ['nl' => 'Zomer', 'en' => 'Summer'],
        'autumn' => ['nl' => 'Herfst', 'en' => 'Autumn'],
        'winter' => ['nl' => 'Winter', 'en' => 'Winter'],
    ];

    public static function current(?DateTimeInterface $date = null): string
    {
        $date = $date ? CarbonImmutable::instance($date)->setTimezone('Europe/Amsterdam') : CarbonImmutable::now('Europe/Amsterdam');
        return match (true) {
            $date->month >= 3 && $date->month <= 5 => 'spring',
            $date->month >= 6 && $date->month <= 8 => 'summer',
            $date->month >= 9 && $date->month <= 11 => 'autumn',
            default => 'winter',
        };
    }
}
