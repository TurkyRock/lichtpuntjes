@extends('community.layout')
@section('title', app()->getLocale()==='en'?'Support Jouw Lichtpuntje':'Steun Jouw Lichtpuntje')
@push('page-styles')<link rel="stylesheet" href="/css/support-compact.css?v={{ filemtime(public_path('css/support-compact.css')) }}">@endpush
@section('content')
@php($en=app()->getLocale()==='en')
<div class="support-page support-compact-promise">
<div class="support-grid"><div class="support-action-column"><section class="support-paper support-form"><span class="support-kicker">{{ $en?'Entirely voluntary':'Helemaal vrijwillig' }}</span><h2>{{ $en?'I would like to help':'Ik help graag mee' }}</h2>
@if(!$ready)<p class="support-note" role="status">{{ $en?'Contributions are not available yet. Thank you for taking a look!':'Bijdragen kan binnenkort. Bedankt dat je alvast even komt kijken!' }}</p>@endif
<form action="/steun/betalen" method="post" id="support-form">@csrf<input type="hidden" name="attempt" value="{{ $attempt }}">
<fieldset class="support-choices"><legend>{{ $en?'How often?':'Hoe vaak?' }}</legend><label><input type="radio" name="frequency" value="once" @checked(old('frequency',$initialFrequency??'once')==='once')> {{ $en?'One time':'Eenmalig' }}</label><label><input type="radio" name="frequency" value="monthly" @checked(old('frequency',$initialFrequency??'once')==='monthly')> {{ $en?'Monthly':'Maandelijks' }}</label></fieldset>
<fieldset class="support-amounts"><legend>{{ $en?'Choose your amount':'Kies je bedrag' }}</legend>@foreach([2,5,10] as $amount)<button type="button" class="support-amount" data-support-amount="{{ $amount }}">€{{ $amount }}</button>@endforeach</fieldset>
<label for="support-amount">{{ $en?'Amount in euros (or choose your own)':'Bedrag in euro’s (of kies zelf)' }}</label><input id="support-amount" name="amount" inputmode="decimal" value="{{ old('amount','5,00') }}" required maxlength="10">
<div id="support-monthly" @if(old('frequency',$initialFrequency??'once')!=='monthly') hidden @endif><p>{{ $en?'The first payment is via iDEAL. The same amount is then collected every month by SEPA Direct Debit, starting one month later.':'Je betaalt de eerste keer via iDEAL. Daarna wordt hetzelfde bedrag iedere maand via SEPA-incasso afgeschreven, vanaf een maand later.' }}</p><label class="support-consent"><input type="checkbox" name="consent" value="1" @checked(old('consent'))> {{ $en?'I authorise this monthly contribution. I can stop it in My contributions.':'Ik geef toestemming voor deze maandelijkse bijdrage. Ik kan deze zelf stoppen bij Mijn bijdragen.' }}</label>@guest<p><a href="/inloggen">{{ $en?'Log in':'Log in' }}</a> {{ $en?'for monthly support, so you can manage it yourself.':'voor maandelijks steunen, zodat je dit zelf kunt beheren.' }}</p>@endguest</div>
<p class="support-payment-summary" id="support-summary" aria-live="polite"></p>
<button class="support-button" @disabled(!$ready)>{{ $en?'Continue to secure payment':'Verder naar veilig betalen' }} ↗</button><p class="small">{{ $en?'One-time contributions do not require an account.':'Eenmalig bijdragen kan zonder account.' }}</p>
</form><p class="small">{{ $en?'Giving is optional. Thank you for being here.':'Geven hoeft niet. Fijn dat je er bent.' }}</p>
</section></div>
<div class="support-story-column"><section class="support-paper support-letter support-family-letter">
<span class="support-kicker">{{ $en?'My promise to you':'Mijn belofte aan jou' }}</span>
<h1 class="support-compact-title">{{ $en?'Always free. No advertising.':'Altijd gratis. Zonder reclame.' }}</h1>
<p class="support-compact-intro">{{ $en?'Thank you for being here. I work with love on Jouw Lichtpuntje: a free place for a little positivity.':'Dank je wel dat je hier bent. Ik werk met liefde aan Jouw Lichtpuntje: een gratis plek voor een beetje positiviteit.' }}</p>
<div class="support-personal-row">
<figure class="support-family-photo"><img src="/images/erik-gezin.jpg" width="1535" height="2048" alt="{{ $en?'Erik with his family, together by an icy lake':'Erik met zijn gezin, samen bij een meer met ijsschotsen' }}" fetchpriority="high"><figcaption>{{ $en?'My family and me — my bright spots.':'Mijn gezin en ik — mijn lichtpuntjes.' }} <span aria-hidden="true">♡</span></figcaption></figure>
<div class="support-personal-copy"><h2 class="support-build-heading"><span>{{ $en?'No business model.':'Geen verdienmodel.' }}</span><span>{{ $en?'Just a love of building.':'Wel plezier in bouwen.' }}</span></h2><p>{{ $en?'Your contribution keeps this place running and helps me keep building.':'Jouw bijdrage houdt deze plek draaiende en helpt mij verder te bouwen.' }}</p><div class="support-purpose-cards" aria-label="{{ $en?'Your contribution helps with':'Jouw bijdrage helpt bij' }}">
<div class="support-purpose"><span class="support-purpose-art support-purpose-art--hosting" aria-hidden="true"></span><p>{{ $en?'Hosting and news':'Hosting en nieuws' }}</p></div>
<div class="support-purpose"><span class="support-purpose-art support-purpose-art--images" aria-hidden="true"></span><p>{{ $en?'Fitting AI images':'Passende AI-beelden' }}</p></div>
<div class="support-purpose"><span class="support-purpose-art support-purpose-art--features" aria-hidden="true"></span><p>{{ $en?'New features':'Nieuwe functies' }}</p></div>
</div>
</div>
</div>
<p class="support-compact-dream"><span aria-hidden="true">♡</span><span>{{ $en?'My dream? To support good causes too, if there is room after covering this site’s costs.':'Mijn droom? Later ook goede doelen steunen, als er na de kosten van deze site ruimte voor is.' }}</span></p>
<p class="support-signature"><span>{{ $en?'Thank you for being here,':'Fijn dat je er bent,' }}</span><span class="support-signature-name">Erik</span></p>
</section></div></div></div>
<script src="/js/support.js" nonce="{{ request()->attributes->get('csp_nonce') }}" defer></script>
@endsection
