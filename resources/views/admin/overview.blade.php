@extends('community.layout')
@section('title','Beheer | Jouw Lichtpuntje')
@section('content')
<div class="admin-heading"><p class="admin-eyebrow">Jouw Lichtpuntje · achter de schermen</p><h1>De zonneschijncentrale</h1><p>Welkom terug, {{ auth()->user()->name }}. Dit vraagt vandaag jouw aandacht.</p></div>
<div class="admin-metrics">
@foreach([['Gebruikers',$counts['users'],'/beheer/gebruikers'],['Artikelen op de site',$counts['articles'],'/beheer/artikelen'],['Open meldingen',$counts['reports'],'/beheer/lichtpuntjes?status=reported']] as [$label,$value,$url])
<a class="panel admin-metric" href="{{ $url }}"><span>{{ $label }}</span><strong>{{ $value }}</strong><span>Bekijken →</span></a>
@endforeach
</div>
<section class="panel"><h2>Op het prikbord</h2><p>Wachtende en twijfelgevallen blijven privé tot ze zijn goedgekeurd.</p><div class="admin-worklist">
@foreach([['Reacties',$counts['comments'],'/beheer/reacties','Controleer reacties en aanvragen voor een menselijke blik.'],['Eigen lichtpuntjes',$counts['stories'],'/beheer/lichtpuntjes?status=review','Beoordeel verhalen en volg vertaling en illustratie.'],['Openbare momentjes',$counts['moments'],'/beheer/momentjes','Alleen gedeelde momentjes; privémomentjes blijven privé.'],['Profielfoto’s',$counts['photos'],'/beheer/profielfotos','Bekijk ingestuurde foto’s en pas de portretregels toe.'],['Gegevensverzoeken',$counts['requests'],'/beheer/gebruikers?filter=privacy','Persoonlijke vragen over inzage, correctie of verwijderen.']] as [$label,$value,$url,$description])
<a href="{{ $url }}" class="admin-workitem"><div><strong>{{ $label }}</strong><span>{{ $description }}</span></div><b class="admin-count">{{ $value }}</b><span aria-hidden="true">→</span></a>
@endforeach</div></section>
<div class="admin-columns"><section class="panel admin-yellow"><h2>Een steuntje in de rug</h2><p>Ontvangen donaties, terugbetalingen en maandelijkse steun bij elkaar. Testbetalingen blijven apart.</p><a class="button" href="/beheer/donaties">Naar donaties</a></section>
<section class="panel"><h2>Even de motor controleren</h2><dl class="admin-facts"><div><dt>Wachtrij</dt><dd>{{ $system['queued']??'Niet beschikbaar' }} taken</dd></div><div><dt>Mislukte taken</dt><dd>{{ $system['failed']??'Niet beschikbaar' }}</dd></div><div><dt>Nieuws met foutstatus</dt><dd>{{ $system['newsErrors'] }}</dd></div><div><dt>Verhalen wachten op afbeelding</dt><dd>{{ $system['imageWaiting'] }}</dd></div><div><dt>Donaties</dt><dd>{{ $system['paymentMode']==='live'?'Livebetalingen':'Teststand' }}</dd></div><div><dt>E-mail</dt><dd>{{ $system['mailConfigured']?'Verzendmethode ingesteld':'Alleen log/test; controleer de mailinstelling' }}</dd></div></dl><p>Wachtrij: {{ $system['queue'] }}. Een teller bewijst niet dat de worker draait. Bij storingen controleer je de worker en het serverlog; fouttaken worden hier niet automatisch opnieuw gestart.</p></section></div>
@endsection
