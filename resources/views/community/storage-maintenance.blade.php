@extends('community.layout')
@section('content')
<h1>Opslag & onderhoud</h1>
<p>Gepubliceerde artikelen en persoonlijke herinneringen blijven bewaard. We ruimen technische restanten op.</p>
<section class="panel">
<h2>{{ number_format($usage['used_bytes']/1048576,1,',','.') }} MB van {{ number_format($usage['quota_bytes']/1048576,0,',','.') }} MB</h2>
<progress max="100" value="{{ min(100,$usage['percent']) }}" aria-label="Gebruikte opslag">{{ $usage['percent'] }}%</progress>
<p>{{ $usage['percent'] }}% gebruikt · gemeten {{ \Carbon\Carbon::parse($usage['measured_at'])->timezone('Europe/Amsterdam')->format('d-m-Y H:i') }}</p>
@if($usage['paused'])<p class="errors" role="alert"><strong>Automatische nieuwsimport gepauzeerd.</strong> Maak ruimte vrij. Zodra het gebruik onder 85% komt en er voldoende schijfruimte is, kan de import verder.</p>
@elseif($usage['warning'])<p class="errors" role="alert">De opslag nadert de limiet of kon niet volledig worden gemeten. Controleer ook het gebruik in Plesk.</p>@endif
@if(!$usage['complete'])<p>Deze meting is onvolledig. Plesk kan ook mail, back-ups of niet-toegankelijke bestanden meetellen.</p>@endif
<p>Bestanden: {{ number_format($usage['file_bytes']/1048576,1,',','.') }} MB · database: {{ number_format($usage['database_bytes']/1048576,1,',','.') }} MB. Plesk blijft leidend voor de hostinglimiet.</p>
<ul>@foreach($usage['breakdown']??[] as $kind=>$bytes)<li>{{ ['news_images'=>'Nieuwsafbeeldingen','moment_images'=>'Momentjesafbeeldingen','profile_photos'=>'Profielfoto’s','logs'=>'Logbestanden','other'=>'Overige bestanden'][$kind]??$kind }}: {{ number_format($bytes/1048576,1,',','.') }} MB</li>@endforeach</ul>
<p>Waarschuwing vanaf 70%. Nieuwe automatische nieuwscollecties pauzeren vanaf 85% of minder dan 100 MB vrije schijfruimte.</p>
</section>
<section class="panel"><h2>Bewaartermijnen</h2>
<div style="overflow-x:auto"><table><thead><tr><th>Onderdeel</th><th>Beleid</th></tr></thead><tbody>
<tr><td>Nieuwsartikelen</td><td>Na 90 dagen in het <a href="/archief">archief</a>; tekst en gebruikte afbeelding blijven bestaan.</td></tr>
<tr><td>Verhalen en momentjes</td><td>Geen automatische vervaldatum.</td></tr>
<tr><td>Verwijderde momentjes</td><td>30 dagen in de persoonlijke prullenbak.</td></tr>
<tr><td>Afgekeurde nieuwsimports</td><td>30 dagen; actieve opdrachten en gekoppelde bijdragen blijven beschermd.</td></tr>
<tr><td>Ongebruikte afbeeldingen en oude beeldmetadata</td><td>7 dagen; gedeelde en nog gebruikte bestanden blijven behouden.</td></tr>
<tr><td>Tijdelijke bestanden</td><td>24 uur, uitsluitend in de onderhoudsmap.</td></tr>
<tr><td>Afgeronde beeldtaken en mislukte queuegegevens</td><td>30 dagen; actieve en nog te beoordelen beelden blijven behouden.</td></tr>
<tr><td>Leesdetails</td><td>90 dagen; totale leestellers van artikelen blijven behouden.</td></tr>
<tr><td>Logs</td><td>Dagelijks comprimeren; logarchieven maximaal 14 dagen bewaren.</td></tr>
<tr><td>Laptop</td><td>Bevestigd ontvangen uitvoer na 7 dagen; alleen geregistreerde Lichtpuntjes-bestanden.</td></tr>
</tbody></table></div>
</section>
<section class="panel"><h2>Opruimen</h2>
<p>Automatisch dagelijks om 03:30 Nederlandse tijd. Een proefcontrole wijzigt niets. Per ronde wordt een begrensd aantal restanten verwerkt.</p>
<form method="post" action="/beheer/opslag">@csrf<button name="action" value="preview">Proefcontrole uitvoeren</button> <button class="quiet" name="action" value="apply">Onderhoud nu uitvoeren</button></form>
@foreach(['Laatste opruimronde'=>$last,'Laatste proefcontrole'=>$preview] as $label=>$result)
@if($result)<h3>{{ $label }}</h3><p>{{ \Carbon\Carbon::parse($result['at'])->timezone('Europe/Amsterdam')->format('d-m-Y H:i') }} · {{ number_format($result['bytes']/1048576,2,',','.') }} MB aan losse bestanden geselecteerd. Database- en logruimte tellen hier niet mee.</p>
<ul>@foreach($result['counts'] as $kind=>$count)<li>{{ ['expired_moments'=>'Verlopen verwijderde momentjes','rejected_imports'=>'Afgekeurde imports','finished_image_jobs'=>'Afgeronde beeldtaken','unused_files'=>'Ongebruikte bestanden','expired_failed_jobs'=>'Verlopen foutgegevens','expired_job_batches'=>'Afgeronde queuebatches','expired_backup_temporary'=>'Verlopen tijdelijke back-upmappen','expired_read_details'=>'Verlopen leesdetails (90 dagen)'][$kind]??$kind }}: {{ $count }}</li>@endforeach</ul>
@if(!empty($result['items']))<details><summary>Bekijk selectie (maximaal 100 regels)</summary><div style="overflow-x:auto"><table><thead><tr><th>Type</th><th>Bestand of record</th><th>KB</th></tr></thead><tbody>@foreach($result['items'] as $item)<tr><td>{{ $item['kind'] }}</td><td>{{ $item['id'] }}</td><td>{{ round($item['bytes']/1024,1) }}</td></tr>@endforeach</tbody></table></div></details>@endif
@endif
@endforeach
</section>
<section class="panel"><h2>Back-ups buiten de server</h2>
@if(!config('retention.backup_remote')||!config('retention.backup_key'))<p class="errors">Nog niet ingesteld. Er is een externe rclone-bestemming en een afzonderlijke encryptiesleutel nodig. Er worden geen lokale back-ups opgestapeld.</p>@else<p>Dagelijks om 04:30. Versleutelde back-ups worden extern bewaard, maximaal 14 dagen. Controleer regelmatig of terugzetten werkt.</p>@endif
@if(config('retention.backup_remote') && (!$backup || \Carbon\Carbon::parse($backup['at'])->lt(now()->subHours(36))))<p class="errors">Geen recente back-uppoging bekend. Controleer de scheduler en back-upinstellingen.</p>@endif
@if($backup)<p>Laatste poging: {{ $backup['at'] }} · {{ $backup['ok']?'extern opgeslagen':'mislukt; controleer het log' }}</p>@endif
</section>
@endsection
