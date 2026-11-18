<?php
namespace App\Support;

/** Public story location, never the writer's private account location. */
class StoryLocation
{
    public static function normalize(?string $place): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u',' ', $place ?? '')));
    }
    public static function places(?string $place, ?string $region): array
    {
        return array_values(array_unique(array_filter([self::normalize($place), self::normalize($region)])));
    }
    public static function matches(array $places, array $preferences): bool
    {
        $wanted = self::places($preferences['place']??null, $preferences['region']??null);
        return (bool) array_intersect($wanted, array_map(fn($p)=>self::normalize($p), array_filter($places,'is_string')));
    }
}
