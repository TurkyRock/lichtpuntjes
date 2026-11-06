@extends('community.layout')
@section('content')
@php($en=app()->getLocale()==='en')
<link rel="stylesheet" href="/css/account-settings.css?v={{ filemtime(public_path('css/account-settings.css')) }}">
<div class="account-settings"><section class="panel"><h1>{{ $en?'Confirm your new email':'Bevestig je nieuwe e-mailadres' }}</h1>
<p>{{ $en?'Use this address for your account:':'Gebruik dit adres voor je account:' }} <strong class="account-email-address">{{ $pending->email }}</strong></p>
<form method="post" action="{{ $confirmUrl }}">@csrf<label><input type="checkbox" name="confirm" value="1" required> {{ $en?'Yes, this is my new email address.':'Ja, dit is mijn nieuwe e-mailadres.' }}</label><p><button>{{ $en?'Confirm email address':'E-mailadres bevestigen' }}</button></p></form>
<a href="/privacy/verzoeken?lang={{ app()->getLocale() }}#account-email">{{ $en?'Back to My details':'Terug naar Mijn gegevens' }}</a></section></div>
@endsection
