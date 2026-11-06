@extends('community.layout')
@section('content')
@php($en=app()->getLocale()==='en')
<h1>{{ $en?'Account security':'Accountbeveiliging' }}</h1>
<section class="panel"><h2>{{ $en?'Change your password':'Wachtwoord wijzigen' }}</h2>
<form action="/beveiliging/wachtwoord" method="post">@csrf
<label for="current-password">{{ $en?'Current password':'Huidig wachtwoord' }}</label><input id="current-password" type="password" name="current_password" autocomplete="current-password" required maxlength="128">
<label for="new-password">{{ $en?'New password phrase':'Nieuwe wachtwoordzin' }}</label><input id="new-password" type="password" name="password" autocomplete="new-password" minlength="15" maxlength="128" required>
<p class="small">{{ $en?'Use at least 15 characters. Spaces are welcome. We check new passwords against known data breaches.':'Gebruik minstens 15 tekens. Spaties mogen. We controleren nieuwe wachtwoorden op bekende datalekken.' }}</p>
<label for="repeat-password">{{ $en?'Repeat new password':'Herhaal het nieuwe wachtwoord' }}</label><input id="repeat-password" type="password" name="password_confirmation" autocomplete="new-password" required maxlength="128">
<p><button>{{ $en?'Save new password':'Nieuw wachtwoord bewaren' }}</button></p></form></section>
<section class="panel"><h2>{{ $en?'Log out everywhere':'Overal uitloggen' }}</h2><p>{{ $en?'This signs you out on every device, including this one. Your stories and moments stay saved.':'Hiermee log je uit op alle apparaten, ook op dit apparaat. Je verhalen en momentjes blijven bewaard.' }}</p>
<form action="/beveiliging/overal-uitloggen" method="post">@csrf<label><input type="checkbox" name="confirm" value="1" required> {{ $en?'Yes, log me out everywhere.':'Ja, log mij overal uit.' }}</label><p><button>{{ $en?'Log out everywhere':'Overal uitloggen' }}</button></p></form></section>
<section class="panel"><h2>{{ $en?'Recent security events':'Recente beveiligingsacties' }}</h2>
@forelse($events as $event)<p>{{ \Carbon\Carbon::parse($event->created_at)->timezone('Europe/Amsterdam')->format('d-m-Y H:i') }} · {{ ['password_changed'=>$en?'Password changed':'Wachtwoord gewijzigd','email_changed'=>$en?'Email changed':'E-mailadres gewijzigd','logged_out_everywhere'=>$en?'Logged out everywhere':'Overal uitgelogd','notification_delayed'=>$en?'Notification could not be queued':'Melding kon niet worden klaargezet'][$event->kind]??($en?'Account updated':'Account bijgewerkt') }}</p>@empty<p>{{ $en?'No security changes recorded yet.':'Nog geen beveiligingswijzigingen geregistreerd.' }}</p>@endforelse
<p class="small">{{ $en?'Only you can see this list. We do not record your private texts, passwords or location here.':'Alleen jij ziet deze lijst. We bewaren hier geen privéteksten, wachtwoorden of locatiegegevens.' }}</p></section>
@endsection
