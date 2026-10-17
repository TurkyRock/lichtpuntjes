@extends('community.layout')
@section('content')
@include('shared.privacy-note')<section class="panel auth-panel"><h1>{{ ['register'=>'Kom erbij. Breng je glimlach mee.','login'=>'Welkom terug, zonnetje.','forgot'=>'Even je wachtwoord kwijt?','reset'=>'Een frisse start.'][$mode] }}</h1>
@if($mode==='register')@include('community.intentions')@include('community.points-explained')@endif
@if(in_array($mode, ['register','login']))
@include('community.social-buttons')
@endif
<form method="post" action="{{ ['register'=>'/aanmelden','login'=>'/inloggen','forgot'=>'/wachtwoord-vergeten','reset'=>'/reset-password'][$mode] }}">@csrf
@if($mode==='register')<label for="name">Zo mogen we je noemen</label><input id="name" name="name" value="{{ old('name') }}" maxlength="40" minlength="2" autocomplete="nickname" required><p class="small">Deze naam staat bij je reacties. Je e-mailadres blijft privé.</p><input type="hidden" name="invite" value="{{ $invite ?? '' }}"><div class="honeypot" aria-hidden="true"><label>Laat dit leeg<input name="website" tabindex="-1" autocomplete="off"></label></div>@endif
@if($mode==='reset')<input type="hidden" name="token" value="{{ $token }}">@endif
<label for="email">E-mailadres</label><input id="email" type="email" name="email" value="{{ old('email', $email ?? '') }}" maxlength="254" autocomplete="email" required>
@if($mode!=='forgot')<label for="password">Wachtwoord</label><input id="password" type="password" name="password" autocomplete="{{ $mode==='login'?'current-password':'new-password' }}" maxlength="128" required>@endif
@if(in_array($mode,['register','reset']))<p class="small">Minimaal 12 tekens, met een hoofdletter, kleine letter en cijfer.</p><label for="password_confirmation">Nog een keer je wachtwoord</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" maxlength="128" required>@endif
@if($mode==='register')<label><input type="checkbox" name="intentions" value="1" required> Ik deel leuke verhalen en reageer vriendelijk. Ik ga akkoord met de <a href="/huisregels" target="_blank" rel="noopener">huisregels</a>.</label>@endif
@if($mode==='login')<label><input type="checkbox" name="remember" value="1"> Onthoud mij op dit apparaat</label>@endif
<p><button>{{ ['register'=>'Ja, ik breng een beetje zon ☀','login'=>'Inloggen','forgot'=>'Stuur een herstellink','reset'=>'Bewaar nieuw wachtwoord'][$mode] }}</button></p></form>
@if($mode==='login')<p><a href="/wachtwoord-vergeten">Wachtwoord vergeten?</a> · <a href="/aanmelden">Nog geen account? Doe mee.</a></p>@else<p><a href="/inloggen">Terug naar inloggen</a></p>@endif</section>@endsection
