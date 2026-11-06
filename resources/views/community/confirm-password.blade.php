@extends('community.layout')
@section('content')
@php($en=app()->getLocale()==='en')
<section class="panel auth-panel"><h1>{{ $en?'Confirm it is you':'Even bevestigen dat jij het bent' }}</h1><p>{{ $en?'For sensitive administrator actions, confirm your password again. This confirmation lasts 30 minutes.':'Voor gevoelige beheeracties vragen we je wachtwoord nog even. Deze bevestiging geldt 30 minuten.' }}</p>
<form action="/beveiliging/bevestigen" method="post">@csrf<label for="password">{{ $en?'Your password':'Je wachtwoord' }}</label><input id="password" name="password" type="password" autocomplete="current-password" required maxlength="128"><p><button>{{ $en?'Confirm':'Bevestigen' }}</button></p></form>
<p><a href="/wachtwoord-vergeten">{{ $en?'Forgot your password or use social login?':'Wachtwoord vergeten of gebruik je sociaal inloggen?' }}</a></p><p class="small">{{ $en?'Signing in again with your connected provider also gives you a fresh confirmation.':'Opnieuw inloggen via je gekoppelde aanbieder geeft je ook een nieuwe bevestiging.' }}</p></section>
@endsection
