@php($supportEn=app()->getLocale()==='en')
<aside class="support-invitation-card" @if(empty($supportPopup)) hidden @endif aria-labelledby="supportInvitationTitle">
<button type="button" class="support-invitation-close" data-dismiss-support aria-label="{{ $supportEn?'Dismiss support invitation':'Steunuitnodiging sluiten' }}">×</button>
<img class="support-invitation-sun" src="/images/account-paper-sun.svg" width="72" height="72" alt="">
<div class="support-invitation-copy"><h2 id="supportInvitationTitle">{{ $supportEn?'A little help for Lichtpuntje?':'Een klein steuntje voor Lichtpuntje?' }}</h2><p>{{ $supportEn?'I would love to keep this place free and without advertising. Would you like to help?':'Ik houd deze plek graag gratis en reclamevrij. Wil je helpen?' }}</p><div class="support-invitation-actions"><a class="support-button" href="/steun?lang={{ app()->getLocale() }}">{{ $supportEn?'Support Lichtpuntje':'Steun Lichtpuntje' }} →</a><button type="button" class="support-invitation-later" data-dismiss-support>{{ $supportEn?'Not now':'Niet nu' }}</button></div></div><span class="support-invitation-signature" aria-hidden="true">Erik</span>
</aside>
