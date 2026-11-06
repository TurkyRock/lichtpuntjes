@php($emailEn=app()->getLocale()==='en')
<section class="panel" id="account-email"><h2>{{ $emailEn?'Your email address':'Jouw e-mailadres' }}</h2>
<p>{{ $emailEn?'Current address:':'Huidig adres:' }} <strong class="account-email-address">{{ auth()->user()->email }}</strong></p>
<p>{{ $emailEn?'Your email is private. Your current address keeps working until you confirm the new one. After confirmation, sign in again on your devices.':'Je e-mailadres is privé. Je huidige adres blijft werken totdat je het nieuwe bevestigt. Daarna log je opnieuw in op je apparaten.' }}</p>
@if($pendingEmailChange)
<p>{{ $emailEn?'Awaiting confirmation at:':'Wacht op bevestiging via:' }} <strong class="account-email-address">{{ $pendingEmailChange->email }}</strong>. {{ $emailEn?'The email link is valid for 60 minutes.':'De link in de e-mail is 60 minuten geldig.' }}</p>
<form method="post" action="/profiel/email/annuleren?lang={{ app()->getLocale() }}">@csrf<button class="quiet">{{ $emailEn?'Cancel change':'Wijziging annuleren' }}</button></form>
@endif
<form method="post" action="/profiel/email?lang={{ app()->getLocale() }}">@csrf
<label for="new-email">{{ $emailEn?'New email address':'Nieuw e-mailadres' }}</label><input id="new-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="254" required>
<label for="repeat-email">{{ $emailEn?'Repeat new email address':'Herhaal je nieuwe e-mailadres' }}</label><input id="repeat-email" type="email" name="email_confirmation" autocomplete="email" maxlength="254" required>
<label for="email-password">{{ $emailEn?'Current password':'Huidig wachtwoord' }}</label><input id="email-password" type="password" name="email_password" autocomplete="current-password" maxlength="128" required>
<p class="small">{{ $emailEn?'Only use Google, Apple or Microsoft to sign in? Set a password first using “Forgot password” with your current email. Your connected sign-in remains available.':'Log je alleen in met Google, Apple of Microsoft? Stel dan eerst via ‘Wachtwoord vergeten’ een wachtwoord in met je huidige e-mailadres. Je gekoppelde inlogmethode blijft werken.' }} <a href="/wachtwoord-vergeten?lang={{ app()->getLocale() }}">{{ $emailEn?'Forgot password':'Wachtwoord vergeten' }}</a></p>
<button>{{ $emailEn?'Send confirmation email':'Bevestigingsmail versturen' }}</button></form></section>
