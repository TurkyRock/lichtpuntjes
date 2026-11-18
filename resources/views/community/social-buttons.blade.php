@php
$providers = !empty($showRegistrationProviders) ? ['google', 'microsoft'] : app(\App\Services\SocialOAuth::class)->enabled();
$en = app()->getLocale() === 'en';
@endphp
@if(count($providers))
<div class="social-signin" aria-label="{{ $en ? 'Continue with your account' : 'Doorgaan met je account' }}">
@foreach($providers as $provider)
<a class="social-signin-button" @if(!empty($rememberControlId))data-remember-provider @endif href="{{ '/auth/'.$provider.'/redirect?lang='.app()->getLocale().(!empty($invite) ? '&invite='.urlencode($invite) : '') }}"><img class="provider-mark" src="{{ '/images/social/'.$provider.($provider === 'google' ? '.png' : '.svg') }}" alt="" width="20" height="20" aria-hidden="true">{{ ($en ? 'Continue with ' : 'Doorgaan met ').ucfirst($provider) }}</a>
@endforeach
@if(empty($compact))<p class="small">{{ $en ? 'We receive your basic profile and email address, never your password or access to your inbox. You confirm our community rules when joining.' : 'We ontvangen je basisprofiel en e-mailadres, nooit je wachtwoord of toegang tot je mailbox. Bij je eerste aanmelding bevestig je onze huisregels.' }}</p>@endif
</div>
<p class="signin-divider"><span>{{ $en ? 'or with email' : 'of met e-mail' }}</span></p>
@if(!empty($rememberControlId))
<script>
(()=>{const checkbox=document.getElementById(@json($rememberControlId));if(!checkbox)return;const update=()=>document.querySelectorAll('[data-remember-provider]').forEach(link=>{const url=new URL(link.href,location.origin);url.searchParams.set('remember',checkbox.checked?'1':'0');link.href=url.href;});checkbox.addEventListener('change',update);update();})();
</script>
@endif
@endif
