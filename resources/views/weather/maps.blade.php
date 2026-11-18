<section class="weather-maps" aria-label="{{ $en?'Sun and rain maps':'Zon- en regenkaarten' }}">
@foreach(['sun'=>['☀','Waar schijnt de zon?','Where is the sun?','Zon en bewolking · verwachting','Sun and clouds · forecast'], 'rain'=>['☂','Waar vallen de buien?','Where are the showers?','Regenradar · waarnemingen','Rain radar · observations']] as $key=>$map)
<article class="weather-map-card weather-map-{{ $key }}" data-weather-map="{{ $key }}">
<h2><span aria-hidden="true">{{ $map[0] }}</span> {{ $map[$en?2:1] }}</h2>
<p class="weather-map-caption">{{ $map[$en?4:3] }} · <span data-map-place>{{ $cities[$defaultCity]['name'] }}</span></p>
<div class="weather-map-viewport">
<div class="weather-map-intro"><span class="weather-map-symbol" aria-hidden="true">{{ $map[0] }}</span><p>{{ $key==='sun'?($en?'Find breaks in the clouds for your outdoor moment. This is a forecast, not a live sunshine measurement.':'Zoek een gaatje tussen de wolken voor jouw buitenmoment. Dit is een verwachting, geen live meting van zonneschijn.'):($en?'See where rain has been observed. Use the timeline in the map; no radar coverage does not necessarily mean dry weather.':'Bekijk waar regen is waargenomen. Gebruik de tijdlijn in de kaart; geen radardekking betekent niet altijd droog weer.') }}</p><button type="button" data-map-open>{{ $key==='sun'?($en?'Open sun & clouds':'Open zon en wolken'):($en?'Open rain radar':'Open regenradar') }}</button><p class="weather-map-privacy">{{ $en?'Only after opening, Ventusky receives your IP address and the approximate map location.':'Pas na openen ontvangt Ventusky je IP-adres en de benaderde kaartlocatie.' }}</p></div>
</div>
<div class="weather-map-actions"><a data-map-external href="https://www.ventusky.com/?l={{ $key==='sun'?'clouds-total':'radar' }}" target="_blank" rel="noopener noreferrer">{{ $en?'Open on Ventusky ↗':'Open bij Ventusky ↗' }}</a><button type="button" data-map-close hidden>{{ $en?'Close map':'Sluit kaart' }}</button></div>
<p class="weather-map-status" data-map-status role="status" aria-live="polite"></p>
<p class="weather-map-source">{{ $en?'Source':'Bron' }}: <a href="https://www.ventusky.com/" target="_blank" rel="noopener noreferrer">Ventusky</a> · {{ $en?'Time, model and legend are shown in the map.':'Tijdstip, model en legenda staan in de kaart.' }}</p>
</article>
@endforeach
</section>
