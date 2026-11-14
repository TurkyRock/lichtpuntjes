@php($en = app()->getLocale() === 'en')
@push('page-styles')
<link rel="stylesheet" href="/css/registration.css?v={{ filemtime(public_path('css/registration.css')) }}">
@endpush
<section class="registration-board" id="registrationPage" aria-labelledby="registrationTitle">
<div class="registration-content">
<h1 id="registrationTitle">{{ $en ? 'Welcome to Jouw Lichtpuntjes!' : 'Welkom bij Jouw Lichtpuntjes!' }}</h1>
@include('community.social-buttons', ['compact' => true])
<form method="post" action="/aanmelden" class="registration-form">
@csrf
<input type="hidden" name="invite" value="{{ $invite ?? '' }}">
<div class="honeypot" aria-hidden="true"><label>{{ $en ? 'Leave this empty' : 'Laat dit leeg' }}<input name="website" tabindex="-1" autocomplete="off"></label></div>
<div class="registration-fields">
<div><label for="name">{{ $en ? 'Display name' : 'Schermnaam' }}</label><input id="name" name="name" value="{{ old('name') }}" minlength="2" maxlength="40" autocomplete="nickname" required></div>
<div><label for="email">{{ $en ? 'Email address' : 'E-mailadres' }}</label><input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="254" autocomplete="email" required></div>
<div><label for="password">{{ $en ? 'Password' : 'Wachtwoord' }}</label><input id="password" type="password" name="password" minlength="15" maxlength="128" autocomplete="new-password" aria-describedby="registrationPasswordHint" required></div>
<div><label for="password_confirmation">{{ $en ? 'Repeat password' : 'Herhaal wachtwoord' }}</label><input id="password_confirmation" type="password" name="password_confirmation" minlength="15" maxlength="128" autocomplete="new-password" required></div>
</div>
<p class="registration-hint" id="registrationPasswordHint">{{ $en ? 'At least 15 characters. A long password phrase works well.' : 'Minimaal 15 tekens. Een lange wachtwoordzin mag ook.' }}</p>
<label class="registration-consent"><input type="checkbox" name="intentions" value="1" @checked(old('intentions')) required><span>{{ $en ? 'I agree to the' : 'Ik ga akkoord met de' }} <a href="/huisregels?lang={{ app()->getLocale() }}" target="_blank" rel="noopener">{{ $en ? 'community rules' : 'huisregels' }}</a> {{ $en ? 'and' : 'en' }} <a href="/privacy?lang={{ app()->getLocale() }}" target="_blank" rel="noopener">{{ $en ? 'privacy statement' : 'privacyverklaring' }}</a>.</span></label>
<button class="registration-submit" type="submit">{{ $en ? 'Create my account' : 'Maak mijn account' }}</button>
</form>
<p class="registration-login">{{ $en ? 'Already have an account?' : 'Al een account?' }} <a href="/inloggen?lang={{ app()->getLocale() }}">{{ $en ? 'Log in' : 'Inloggen' }}</a></p>
</div>
</section>
