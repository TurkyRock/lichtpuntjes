@php($en = app()->getLocale() === 'en')
<link rel="stylesheet" href="/css/registration.css?v={{ filemtime(public_path('css/registration.css')) }}">
<section class="registration-board" id="registrationPage" aria-labelledby="registrationTitle">
<div class="registration-content">
<h1 id="registrationTitle">{{ $en ? 'Welcome to Lichtpuntje!' : 'Welkom bij Lichtpuntje!' }}</h1>
@include('community.social-buttons', ['compact' => true, 'showRegistrationProviders' => true])
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
<label class="registration-consent"><input type="checkbox" name="intentions" value="1" @checked(old('intentions')) required><span>{{ $en ? 'I agree to the' : 'Ik ga akkoord met de' }} <a href="/huisregels?lang={{ app()->getLocale() }}" target="_blank" rel="noopener">{{ $en ? 'community rules' : 'huisregels' }}</a> {{ $en ? 'and have read the' : 'en heb kennisgenomen van de' }} <a href="/privacy?lang={{ app()->getLocale() }}" target="_blank" rel="noopener">{{ $en ? 'privacy statement' : 'privacyverklaring' }}</a>.</span></label>
<label class="registration-consent registration-adult"><input type="checkbox" name="adult" value="1" @checked(old('adult') == '1') required @error('adult') aria-invalid="true" aria-describedby="registrationAdultError" @enderror><span>{{ $en?'I am 18 or older.':'Ik ben 18 jaar of ouder.' }}</span></label>
@error('adult')<p class="registration-error" id="registrationAdultError" role="alert">{{ $en?'Please confirm that you are 18 or older.':'Bevestig dat je 18 jaar of ouder bent.' }}</p>@enderror
<details class="registration-preferences" @if(request()->query('interesses') === '1' || old('personalisation') || $errors->hasAny(['age_group','place','interests','interests.*','geography'])) open @endif>
<summary>{{ $en?'Choose your news preferences (optional)':'Kies je nieuwsvoorkeuren (vrijwillig)' }}</summary>
@include('community.news-preferences-fields',['user'=>null,'registrationForm'=>true])
</details>
<button class="registration-submit" type="submit">{{ $en ? 'Create my account' : 'Maak mijn account' }}</button>
</form>
<p class="registration-login">{{ $en ? 'Already have an account?' : 'Al een account?' }} <a href="/inloggen?lang={{ app()->getLocale() }}">{{ $en ? 'Log in' : 'Inloggen' }}</a></p>
</div>
</section>
