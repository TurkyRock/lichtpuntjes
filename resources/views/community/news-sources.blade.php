@extends('community.layout')
@section('content')
<h1>Nieuwsbronnen en regionale bakken</h1>
<p>Feeds worden ieder uur tussen 06:00 en 22:00 gecontroleerd. Maximaal twee regionale aanvullingen per uur; internationale aanvullingen delen de bestaande uurronde. Nieuwe verwerking pauzeert bij wachtrijdruk.</p>
<p role="status">{{ $paused?'Nieuwe aanvulling gepauzeerd: eerst de tekst- en beeldwachtrij verwerken.':'Wachtrijen binnen de limiet voor aanvulling.' }}</p>
<p>Een lege regionale bak betekent dat er nog geen passend, gecontroleerd aanbod is. Dat kan ook komen doordat voor die regio nog geen eigen bron is toegelaten. De bronnen hieronder bieden samen geen gegarandeerde lokale dekking van iedere provincie.</p>
<div class="admin-table-scroll"><table role="table" class="admin-mobile-table"><caption>Alle provincies</caption><thead role="rowgroup"><tr role="row"><th scope="col" role="columnheader">Provincie</th><th scope="col" role="columnheader">Gepubliceerd</th><th scope="col" role="columnheader">Kandidaten</th><th scope="col" role="columnheader">In verwerking</th><th scope="col" role="columnheader">Aandacht nodig</th></tr></thead><tbody role="rowgroup">
@foreach($regions as $r)<tr role="row"><th role="rowheader" scope="row" class="admin-card-title">{{ $r['name'] }}</th><td role="cell" data-label="Gepubliceerd">{{ $r['published'] }}</td><td role="cell" data-label="Kandidaten">{{ $r['queued'] }}</td><td role="cell" data-label="In verwerking">{{ $r['processing'] }}</td><td role="cell" data-label="Aandacht nodig">{{ $r['attention'] }}</td></tr>@endforeach
</tbody></table></div>
<h2>Toegelaten aanvullende bronnen</h2><p>De bestaande NewsData.io-koppeling blijft actief voor eigen feitelijke samenvattingen. De API-toegang is geen overdracht van uitgeversrechten en valt buiten de hieronder gecontroleerde directe bronnenlijst.</p>
@foreach($sources as $s)
<section class="panel"><h3>{{ $s['name'] }}</h3>
<p>{{ $s['approved']?'Rechten gecontroleerd':'Gepauzeerd: rechten opnieuw controleren' }} · {{ $s['license'] }} · Beoordeeld: {{ $s['reviewed'] }}</p>
<p>Laatste broncontrole: {{ $s['check']?->checked_at?\Carbon\CarbonImmutable::parse($s['check']->checked_at,'UTC')->timezone('Europe/Amsterdam')->format('d-m H:i'):'nog niet uitgevoerd' }}. Laatste succes: {{ $s['check']?->succeeded_at?\Carbon\CarbonImmutable::parse($s['check']->succeeded_at,'UTC')->timezone('Europe/Amsterdam')->format('d-m H:i'):'nog geen' }} (Nederlandse tijd).</p>
<p>{{ $s['check']?->message??'Wacht op de eerste uurlijkse controle.' }}</p>
<a href="{{ $s['policy'] }}" target="_blank" rel="noopener noreferrer">Hergebruikvoorwaarden ↗</a>
</section>
@endforeach
<p>Gemeente–provinciekoppeling: <a href="https://www.cbs.nl/nl-nl/onze-diensten/methoden/classificaties/overig/gemeentelijke-indelingen-per-jaar/indeling-per-jaar/gemeentelijke-indeling-op-1-januari-2026" target="_blank" rel="noopener noreferrer">CBS, gemeentelijke indeling 2026</a>, <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a>. Omgezet naar een lokale zoektabel; geen bezoekergegevens naar CBS verzonden.</p>
@endsection
