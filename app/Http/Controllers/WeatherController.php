<?php

namespace App\Http\Controllers;

use App\Services\WeatherForecast;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WeatherController extends Controller
{
    public function index(Request $request)
    {
        $requested = $request->query('season');
        $preview = app()->environment('local', 'testing') && is_string($requested) && array_key_exists($requested, \App\Support\WeatherSeason::LABELS)
            ? $requested : null;
        return view('weather.index', [
            'cities' => config('weather.cities'), 'defaultCity' => config('weather.default_city'),
            'season' => $preview ?? \App\Support\WeatherSeason::current(),
            'seasonPreview' => $preview !== null, 'seasonLabels' => \App\Support\WeatherSeason::LABELS,
        ]);
    }

    public function data(Request $request, WeatherForecast $weather)
    {
        $input = $request->validate([
            'city' => ['sometimes', 'string', 'prohibits:latitude,longitude', Rule::in(array_keys(config('weather.cities')))],
            'latitude' => ['required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['required_with:latitude', 'numeric', 'between:-180,180'],
        ]);
        $forecast = isset($input['latitude'], $input['longitude'])
            ? $weather->getForCoordinates((float)$input['latitude'], (float)$input['longitude'])
            : $weather->get($input['city'] ?? config('weather.default_city'));
        if ($forecast && isset($input['latitude'])) $forecast['name'] = app()->getLocale() === 'en' ? 'Your area' : 'Jouw omgeving';
        return response()->json($forecast ?? ['unavailable' => true], $forecast ? 200 : 503)
            ->header('Cache-Control', 'no-store');
    }
}
