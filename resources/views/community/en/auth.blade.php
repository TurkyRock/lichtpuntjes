@extends('community.en.layout')
@section('content')
@include('shared.privacy-note')<section class="panel auth-panel"><h1>{{ ['register'=>'Come on in. Bring your smile.','login'=>'Welcome back, sunshine.','forgot'=>'Forgot your password?','reset'=>'A fresh start.'][$mode] }}</h1>
@if($mode==='register')@include('community.en.intentions')@include('community.points-explained')@endif
@if(in_array($mode, ['register','login']))
@include('community.social-buttons')
@endif
<form method="post" action="{{ ['register'=>'/aanmelden','login'=>'/inloggen','forgot'=>'/wachtwoord-vergeten','reset'=>'/reset-password'][$mode] }}">@csrf
@if($mode==='register')<label for="name">Your public name</label><input id="name" name="name" value="{{ old('name') }}" maxlength="40" minlength="2" autocomplete="nickname" required><p class="small">This name appears with your comments. Your email stays private.</p><input type="hidden" name="invite" value="{{ $invite ?? '' }}"><div class="honeypot" aria-hidden="true"><label>Leave this empty<input name="website" tabindex="-1" autocomplete="off"></label></div>@endif
@if($mode==='reset')<input type="hidden" name="token" value="{{ $token }}">@endif
<label for="email">Email address</label><input id="email" type="email" name="email" value="{{ old('email', $email ?? '') }}" maxlength="254" autocomplete="email" required>
@if($mode!=='forgot')<label for="password">Password</label><input id="password" type="password" name="password" autocomplete="{{ $mode==='login'?'current-password':'new-password' }}" maxlength="128" required>@endif
@if(in_array($mode,['register','reset']))<p class="small">At least 15 characters. Choose a long password phrase; spaces are welcome. Known breached passwords are rejected.</p><label for="password_confirmation">Repeat your password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" maxlength="128" required>@endif
@if($mode==='register')<label><input type="checkbox" name="intentions" value="1" required> I share happy stories and respond kindly. I accept the <a href="/huisregels?lang=en" target="_blank" rel="noopener">community rules</a>.</label>@endif
@if($mode==='login')<label><input type="checkbox" name="remember" value="1"> Remember me on this device</label>@endif
<p><button>{{ ['register'=>'Yes, I’ll bring some sunshine ☀','login'=>'Log in','forgot'=>'Send a reset link','reset'=>'Save new password'][$mode] }}</button></p></form>
@if($mode==='login')<p><a href="/wachtwoord-vergeten?lang=en">Forgot your password?</a> · <a href="/aanmelden?lang=en">No account yet? Join us.</a></p>@else<p><a href="/inloggen?lang=en">Back to login</a></p>@endif</section>@endsection
