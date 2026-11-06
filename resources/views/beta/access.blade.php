@extends('community.layout')
@section('title','Welkom bij de proefronde | Jouw Lichtpuntje')
@push('page-styles')<link rel="stylesheet" href="/css/beta-tests.css?v={{ filemtime(public_path('css/beta-tests.css')) }}">@endpush
@section('content')
<section class="beta-welcome beta-sheet"><span class="beta-sticker" aria-hidden="true">☀</span><p class="beta-eyebrow">Drie frisse blikken maken het verschil</p><h1>Help jij het een beetje lichter maken?</h1><p>Welkom bij de besloten proefronde van Jouw Lichtpuntje. Met jouw testlink kun je rustig rondkijken en je ervaringen op een apart scherm bewaren.</p><p>Een foutje gevonden? Dat is juist nuttig. Je hoeft niets op te lossen en niets te betalen. Dank je wel voor je tijd!</p><form method="post" action="/testgroep/toegang" id="betaAccess">@csrf<label for="betaCode">Jouw persoonlijke testcode</label><input id="betaCode" name="code" type="password" minlength="48" maxlength="48" autocomplete="off" required><p class="small">De code wordt ingevuld als je Eriks persoonlijke testlink opent.</p><button class="beta-button" type="submit">Ik kijk graag mee →</button></form></section>
<script nonce="{{ request()->attributes->get('csp_nonce') }}">(()=>{const code=location.hash.slice(1);if(/^[a-f0-9]{48}$/.test(code)){document.getElementById('betaCode').value=code;history.replaceState(null,'',location.pathname);}})();</script>
@endsection
