<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class WeatherForecast
{
    public function get(string $city): ?array
    {
        $place = config("weather.cities.{$city}");
        if (!$place) return null;
        $key = 'weather:v1:'.$city;
        $cached = Cache::get($key);
        $now = now()->timestamp;
        if (is_array($cached) && ($cached['days'][0]['date'] ?? null) !== CarbonImmutable::now('Europe/Amsterdam')->toDateString()) $cached = null;
        if (is_array($cached) && $now - $cached['fetched_at'] < 1200) return $cached + ['stale' => false];
        // A short refresh lease also limits retries while the provider is unavailable.
        if (!Cache::add($key.':refresh', true, 60)) return $this->stale($cached);
        try {
            $data = Http::acceptJson()->connectTimeout(10)->timeout(20)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $place['latitude'], 'longitude' => $place['longitude'],
                'timezone' => 'Europe/Amsterdam', 'forecast_days' => 7,
                'current' => 'temperature_2m,weather_code,is_day',
                'hourly' => 'temperature_2m,precipitation_probability,precipitation,sunshine_duration,weather_code',
                'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,sunshine_duration,daylight_duration,sunrise,sunset,weather_code',
            ])->throw()->json();
            $forecast = $this->normalize($data, $city, $place['name']);
            Cache::put($key, $forecast, 7200);
            return $forecast + ['stale' => false];
        } catch (Throwable $e) {
            report($e);
            return $this->stale($cached);
        }
    }

    private function stale($cached): ?array
    {
        return is_array($cached) && now()->timestamp - $cached['fetched_at'] < 7200
            ? $cached + ['stale' => true] : null;
    }

    private function number(array $data, string $key, ?int $index = null): ?float
    {
        $value = $index === null ? ($data[$key] ?? null) : ($data[$key][$index] ?? null);
        return is_numeric($value) && is_finite((float)$value) ? (float)$value : null;
    }

    private function normalize($data, string $city, string $name): array
    {
        if (!is_array($data) || count($data['daily']['time'] ?? []) < 7 || !is_array($data['hourly']['time'] ?? null)) {
            throw new RuntimeException('Incomplete weather forecast');
        }
        $daily = $data['daily'];
        $hourly = $data['hourly'];
        $days = [];
        foreach (array_slice($daily['time'], 0, 7) as $i => $date) {
            if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) throw new RuntimeException('Invalid forecast date');
            $hours = [];
            foreach ($hourly['time'] as $j => $time) {
                if (!is_string($time) || substr($time, 0, 10) !== $date) continue;
                $hours[] = [
                    'time' => substr($time, 11, 5), 'temperature' => $this->number($hourly, 'temperature_2m', $j),
                    'rain_probability' => $this->number($hourly, 'precipitation_probability', $j),
                    'rain' => $this->number($hourly, 'precipitation', $j),
                    'sun' => $this->number($hourly, 'sunshine_duration', $j),
                    'code' => $this->number($hourly, 'weather_code', $j),
                ];
            }
            $sun = $this->number($daily, 'sunshine_duration', $i);
            $light = $this->number($daily, 'daylight_duration', $i);
            $days[] = [
                'date' => $date, 'max' => $this->number($daily, 'temperature_2m_max', $i),
                'min' => $this->number($daily, 'temperature_2m_min', $i),
                'rain' => $this->number($daily, 'precipitation_sum', $i), 'sun' => $sun, 'light' => $light,
                'light_fraction' => $sun !== null && $light !== null && $light > 0 ? max(0, min(1, $sun / $light)) : null,
                'sunrise' => isset($daily['sunrise'][$i]) ? substr($daily['sunrise'][$i], 11, 5) : null,
                'sunset' => isset($daily['sunset'][$i]) ? substr($daily['sunset'][$i], 11, 5) : null,
                'code' => $this->number($daily, 'weather_code', $i), 'hours' => $hours,
            ];
        }
        if ($days[0]['date'] !== CarbonImmutable::now('Europe/Amsterdam')->toDateString()) throw new RuntimeException('Forecast is not for today');
        $current = is_array($data['current'] ?? null) ? $data['current'] : [];
        return [
            'city' => $city, 'name' => $name, 'timezone' => 'Europe/Amsterdam', 'fetched_at' => now()->timestamp,
            'current' => ['temperature' => $this->number($current, 'temperature_2m'),
                'code' => $this->number($current, 'weather_code'), 'is_day' => $this->number($current, 'is_day'),
                'time' => $current['time'] ?? null], 'days' => $days,
        ];
    }
}
