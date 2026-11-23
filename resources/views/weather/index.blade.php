@extends('community.layout')
@php($en = app()->getLocale() === 'en')
@section('title', $en ? 'Hang a little light in your day · Weather' : 'Hang wat licht aan je dag · Weer')
@push('page-styles')
<link rel="stylesheet" href="/css/weather.css?v={{ filemtime(public_path('css/weather.css')) }}">
<link rel="stylesheet" href="/css/weather-compact.css?v={{ filemtime(public_path('css/weather-compact.css')) }}">
<script src="/js/weather-seasons.js?v={{ filemtime(public_path('js/weather-seasons.js')) }}" defer nonce="{{ request()->attributes->get('csp_nonce') }}"></script>
<script src="/js/weather-maps.js?v={{ filemtime(public_path('js/weather-maps.js')) }}" defer nonce="{{ request()->attributes->get('csp_nonce') }}"></script>
@endpush
@section('content')
<div class="weather-page weather-compact weather-outside weather-season-{{ $season }}" id="weatherPage" data-locale="{{ $en ? 'en' : 'nl' }}" data-default-city="{{ $defaultCity }}" data-season="{{ $season }}" data-season-preview="{{ $seasonPreview ? 'true' : 'false' }}">
<div class="weather-board-content">
<div class="weather-heading"><div><span class="weather-season-tag" id="weatherSeasonLabel">{{ $seasonLabels[$season][$en ? 'en' : 'nl'] }}</span><h1>{{ $en ? 'A little fresh air' : 'Even een frisse neus' }}</h1><p>{{ $en ? 'The weather, what to wear and a little bright spot.' : 'Het weer, wat je aantrekt en een klein lichtpuntje.' }}</p></div>
<form class="weather-location"><label for="weatherCity">{{ $en ? 'Weather near' : 'Het weer bij' }}</label><div><select id="weatherCity" name="city">@foreach($cities as $key => $city)<option value="{{ $key }}" data-latitude="{{ $city['latitude'] }}" data-longitude="{{ $city['longitude'] }}" @selected($key === $defaultCity)>{{ $city['name'] }}</option>@endforeach</select><button type="submit">{{ $en ? 'Show' : 'Toon' }}</button></div><button type="button" class="weather-geolocate" id="weatherLocate" aria-describedby="weatherLocationHint">{{ $en ? 'Use my location' : 'Gebruik mijn locatie' }}</button><span class="weather-location-hint" id="weatherLocationHint">{{ $en ? 'With your permission, your approximate location is sent to the weather provider.' : 'Met jouw toestemming gaat je benaderde locatie naar de weerbron.' }}</span><span class="weather-location-feedback" id="weatherLocationFeedback" role="status" aria-live="polite"></span></form></div>
<p id="weatherStatus" role="status" aria-live="polite">{{ $en ? 'Fetching the forecast…' : 'De weersverwachting wordt opgehaald…' }}</p>
<div id="weatherContent" hidden>
<section class="weather-paper weather-today" aria-labelledby="weatherLevel">
<div class="weather-today-copy"><p class="weather-eyebrow" id="weatherDate"></p><h2 id="weatherLevel"></h2><p class="weather-temperature"><span id="weatherMax"></span><small>max.</small></p><p class="weather-message" id="weatherMessage"></p><p class="weather-small">☂ <b id="weatherRain"></b> {{ $en ? 'precipitation expected' : 'neerslag verwacht' }}</p></div>
</section>
</div>

<div id="weatherForecastDetails" hidden>
<div class="weather-daily-advice">
<section class="weather-outfit">
<div class="weather-outfit-heading">
<h2 id="weatherOutfitTitle">{{ $en ? 'What should I wear today?' : 'Wat doe ik vandaag aan?' }}</h2>
<img src="/images/weather/kledingtwijfel.webp" alt="" width="320" height="240" class="weather-outfit-illustration" decoding="async">
</div>
<p id="weatherOutfit"></p><p class="weather-advice-note">{{ $en ? 'Based on daytime temperatures and rain; adjust to how you feel.' : 'Op basis van temperatuur overdag en regen; pas aan aan jouw gevoel.' }}</p></section>
<section class="weather-outdoor"><h2>{{ $en ? 'A little outdoor joy' : 'Jouw buitenmoment' }}</h2><p id="weatherOutdoorTime"></p><a href="{{ $en ? '/en' : '/' }}">{{ $en ? 'Read a bright spot' : 'Lees een lichtpuntje' }}</a></section>
</div>
<section class="weather-clothesline" aria-label="{{ $en ? 'Morning, afternoon and evening' : 'Ochtend, middag en avond' }}">
@foreach([[$en ? 'Morning' : 'Ochtend','10:00'],[$en ? 'Afternoon' : 'Middag','14:00'],[$en ? 'Evening' : 'Avond','20:00']] as $i => $part)
<article class="weather-paper weather-daypart {{ $i === 1 ? 'weather-yellow' : '' }}"><img class="weather-pin" src="/images/category-clothespin.svg" alt="" width="22" height="45"><h3>{{ $part[0] }}</h3><p class="weather-part-time">{{ $part[1] }}</p><div class="weather-part-value"><span id="weatherPart{{ $i }}Icon" aria-hidden="true">—</span><strong id="weatherPart{{ $i }}Temp">—°</strong></div><p class="weather-small"><span id="weatherPart{{ $i }}Sun">—</span> {{ $en ? 'min sun' : 'min zon' }} · ☂ <span id="weatherPart{{ $i }}Rain">—</span>%</p></article>
@endforeach
</section>
<figure class="weather-nature-photo"><div class="weather-stamp"><img id="weatherNaturePhoto" src="/images/weather/{{ $season }}-postcard.webp" alt="{{ $en ? 'An atmospheric nature image for this season' : 'Een sfeervol natuurbeeld bij dit seizoen' }}" width="480" height="640" loading="lazy" decoding="async"></div></figure>
<details class="weather-extra weather-week-extra"><summary>{{ $en ? 'The next seven days' : 'De komende zeven dagen' }}</summary><p class="weather-small">{{ $en ? 'Choose a day: the forecast and clothing advice change along with it.' : 'Kies een dag: de verwachting en het kledingadvies veranderen mee.' }}</p><div class="weather-week" id="weatherWeek" role="group" aria-label="{{ $en ? 'Choose a day' : 'Kies een dag' }}"></div>
</details>
<div class="weather-fineprint"><details class="weather-extra" id="weatherHourlyDetails"><summary>{{ $en ? 'Hourly forecast' : 'Bekijk het weer per uur' }}</summary><section class="weather-paper weather-hour-section" aria-labelledby="weatherHourTitle"><h2 id="weatherHourTitle">{{ $en ? 'The weather hour by hour' : 'Het weer van uur tot uur' }}</h2><p class="weather-small">{{ $en ? 'Sunshine minutes, precipitation amount and probability refer to the hour ending at the displayed time.' : 'Zonminuten, neerslag en neerslagkans horen bij het uur dat eindigt op het getoonde tijdstip.' }}</p><div class="weather-hours" id="weatherHours" tabindex="0" aria-label="{{ $en ? 'Hourly forecast, scroll sideways for more' : 'Uurverwachting, schuif opzij voor meer uren' }}"></div></section>

</details><details class="weather-extra" id="weatherRadar"><summary>{{ $en ? 'Rain radar and cloud map' : 'Regenradar en wolkenkaart' }}</summary>@include('weather.maps')</details><p class="weather-source"><span id="weatherUpdated"></span> · <a href="https://open-meteo.com/" target="_blank" rel="noopener">Open-Meteo</a> (<a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener">CC BY 4.0</a>). {{ $en ? 'Forecasts may change.' : 'Een verwachting kan veranderen.' }}</p>
</div></div><noscript><p>{{ $en ? 'Enable JavaScript to load the current forecast.' : 'Zet JavaScript aan om de actuele weersverwachting te laden.' }}</p></noscript>
</div></div>
@endsection
