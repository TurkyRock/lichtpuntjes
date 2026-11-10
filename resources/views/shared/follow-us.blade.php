@php($followEn = app()->getLocale() === 'en')
<dialog id="followUsDialog" class="follow-us-dialog" aria-labelledby="followUsTitle">
<div class="follow-us-head"><p>Jouw Lichtpuntje</p><button type="button" data-follow-close aria-label="{{ $followEn ? 'Close' : 'Sluiten' }}">×</button></div>
<h2 id="followUsTitle" data-follow-nl="Volg een beetje positiviteit" data-follow-en="Follow a little positivity">{{ $followEn ? 'Follow a little positivity' : 'Volg een beetje positiviteit' }}</h2>
<p data-follow-nl="Ook tussen je dagelijkse berichten door een lichtpuntje vinden? Hier vind je onze kanalen." data-follow-en="A little bright spot in your daily feed? Find our channels here.">{{ $followEn ? 'A little bright spot in your daily feed? Find our channels here.' : 'Ook tussen je dagelijkse berichten door een lichtpuntje vinden? Hier vind je onze kanalen.' }}</p>
<div class="follow-us-platforms">
@foreach(config('social.profiles', []) as $platform => $profile)
@php($followUrl = $profile['url'] ?? null)
@if(is_string($followUrl) && filter_var($followUrl, FILTER_VALIDATE_URL) && str_starts_with($followUrl, 'https://'))
<a href="{{ $followUrl }}" target="_blank" rel="noopener noreferrer" class="follow-us-channel"><strong>{{ $profile['name'] }}</strong><span aria-hidden="true">↗</span></a>
@else
<div class="follow-us-channel follow-us-soon"><strong>{{ $profile['name'] }}</strong><span data-follow-nl="Binnenkort" data-follow-en="Coming soon">{{ $followEn ? 'Coming soon' : 'Binnenkort' }}</span></div>
@endif
@endforeach
</div>
</dialog>
<link rel="stylesheet" href="/css/follow-us.css?v={{ filemtime(public_path('css/follow-us.css')) }}">
<script nonce="{{ request()->attributes->get('csp_nonce') }}" src="/js/follow-us.js?v={{ filemtime(public_path('js/follow-us.js')) }}" defer></script>
