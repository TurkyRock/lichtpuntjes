@php($rememberEn = app()->getLocale() === 'en')
<div class="remember-device-choice">
<input type="hidden" name="remember" value="0" form="community-login-form">
<label for="remember-device" style="display:flex;align-items:center;gap:10px"><input id="remember-device" type="checkbox" name="remember" value="1" form="community-login-form" @checked(old('remember', true)) style="width:auto;flex:none"> {{ $rememberEn ? 'Stay signed in on this device' : 'Ingelogd blijven op dit apparaat' }}</label>
<p class="small">{{ $rememberEn ? 'For up to 30 days. Use this on your own device. Signing out ends remembered access.' : 'Tot 30 dagen. Gebruik dit op je eigen apparaat. Bij uitloggen vervalt deze toegang.' }}</p>
</div>
