@extends(app()->getLocale() === 'en' ? 'community.en.layout' : 'community.layout')
@section('content')
@include('shared.privacy-note')
@php($en = app()->getLocale() === 'en')
<section class="panel auth-panel">
<h1>{{ $en ? 'One more smile, and you’re in.' : 'Nog één glimlach, en je bent erbij.' }}</h1>
<p>{{ $en ? 'Your account was checked by '.ucfirst($pending['provider']).'. Choose your public name and join our positive community.' : 'Je account is gecontroleerd door '.ucfirst($pending['provider']).'. Kies je openbare naam en sluit je aan bij onze positieve community.' }}</p>
@include($en ? 'community.en.intentions' : 'community.intentions')
<form method="post" action="/aanmelden/afronden">
@csrf
<label for="name">{{ $en ? 'Your public name' : 'Zo mogen we je noemen' }}</label>
<input id="name" name="name" value="{{ old('name', $suggestedName) }}" maxlength="40" minlength="2" autocomplete="nickname" required>
<label for="social-email">{{ $en ? 'Email address (private)' : 'E-mailadres (blijft privé)' }}</label>
<input id="social-email" type="email" name="email" value="{{ $pending['email'] ?: old('email') }}" maxlength="254" autocomplete="email" {{ $pending['email'] !== '' ? 'readonly' : '' }} required>
@if(!$pending['email_verified'])
<p class="small">{{ $en ? 'Confirm this address by email before posting comments.' : 'Bevestig dit adres via e-mail voordat je reacties plaatst.' }}</p>
@endif
@if($existing || $errors->has('existing_password'))
<p>{{ $en ? 'Already a member? Your points and comments stay with your existing account. Confirm your password once to connect this sign-in option.' : 'Al lid? Je lichtpunten en reacties blijven bij je bestaande account. Bevestig eenmalig je wachtwoord om deze inlogoptie te koppelen.' }}</p>
<label for="existing-password">{{ $en ? 'Existing Lichtpunt password' : 'Bestaand Lichtpunt-wachtwoord' }}</label>
<input id="existing-password" name="existing_password" type="password" autocomplete="current-password" maxlength="128" required>
<p class="small"><a href="/wachtwoord-vergeten?lang={{ app()->getLocale() }}">{{ $en ? 'Forgot your password?' : 'Wachtwoord vergeten?' }}</a></p>
@endif
<div class="honeypot" aria-hidden="true"><label>Leave empty<input name="website" tabindex="-1" autocomplete="off"></label></div>
<label><input type="checkbox" name="intentions" value="1" required> {{ $en ? 'I share happy stories and respond kindly. I accept the' : 'Ik deel leuke berichten en reageer vriendelijk. Ik accepteer de' }} <a href="/huisregels?lang={{ app()->getLocale() }}" target="_blank" rel="noopener">{{ $en ? 'community rules' : 'huisregels' }}</a>.</label>
<p><button>{{ $en ? 'Let’s bring some sunshine' : 'Ja, ik breng wat licht mee' }}</button></p>
</form>
<p class="small"><a href="/inloggen?lang={{ app()->getLocale() }}">{{ $en ? 'Use another sign-in option' : 'Een andere inlogoptie gebruiken' }}</a></p>
</section>
@endsection
