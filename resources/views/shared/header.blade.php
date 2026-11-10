@php($en = app()->getLocale() === 'en')
@php($home = $home ?? false)
<header class="site-header"><nav class="site-nav" aria-label="{{ $en ? 'Main navigation' : 'Hoofdmenu' }}">
<a href="/?lang={{ $en ? 'en' : 'nl' }}" class="site-brand" aria-label="Jouw Lichtpuntje home"><span class="site-sun" aria-hidden="true"><img src="/images/site-paper-sun.svg" width="37" height="37" alt=""></span><span>Jouw Lichtpuntje<span class="site-dot">.</span></span></a>
<button type="button" id="siteMenuToggle" class="site-menu-toggle" aria-expanded="false" aria-controls="siteMenu" hidden><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg><span>Menu</span></button>
<div class="site-links" id="siteMenu">
<a class="site-text site-support" data-account-link href="/steun?lang={{ $en ? 'en' : 'nl' }}" @if(request()->is('steun')) aria-current="page" @endif><span aria-hidden="true">☀</span> <span data-nav-nl="Steun Lichtpuntje" data-nav-en="Support Lichtpuntje">{{ $en ? 'Support Lichtpuntje' : 'Steun Lichtpuntje' }}</span></a>
<a class="site-text site-share" data-account-link href="/lichtpuntje/delen?lang={{ $en ? 'en' : 'nl' }}" @if(request()->is('lichtpuntje/delen','lichtpuntje/schrijven','mijn-momentjes')) aria-current="page" @endif><svg class="site-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/></svg><span data-nav-nl="Deel een lichtpuntje" data-nav-en="Share a bright spot">{{ $en ? 'Share a bright spot' : 'Deel een lichtpuntje' }}</span></a>

@auth

<a class="site-text" data-account-link href="/profiel?lang={{ $en ? 'en' : 'nl' }}" @if(request()->is('profiel')) aria-current="page" @endif ><svg class="site-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3Z"/></svg><span data-nav-nl="Mijn account" data-nav-en="My account">{{ $en ? 'My account' : 'Mijn account' }}</span></a>
<form method="post" action="/uitloggen" class="site-logout">
@csrf
<button type="submit" class="site-text" ><svg class="site-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 3H3v18h6M10 12h11m-4-4 4 4-4 4"/></svg><span data-nav-nl="Uitloggen" data-nav-en="Log out">{{ $en ? 'Log out' : 'Uitloggen' }}</span></button></form>
@else
<a class="site-text" data-account-link href="/aanmelden?lang={{ $en ? 'en' : 'nl' }}" ><svg class="site-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="7" r="4"/><path d="M2 21v-3a7 7 0 0 1 12-5M19 12v8m-4-4h8"/></svg><span data-nav-nl="Registreren" data-nav-en="Register">{{ $en ? 'Register' : 'Registreren' }}</span></a>
<a class="site-text" data-account-link href="/inloggen?lang={{ $en ? 'en' : 'nl' }}" ><svg class="site-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3Z"/></svg><span data-nav-nl="Inloggen" data-nav-en="Log in">{{ $en ? 'Log in' : 'Inloggen' }}</span></a>
@endauth
</div><div class="site-tools">
<a class="site-weather" data-weather-link data-account-link href="/weer?lang={{ $en ? 'en' : 'nl' }}" aria-label="{{ $en ? 'View the weather' : 'Bekijk het weer' }}" title="{{ $en ? 'Your light meter' : 'Jouw lichtwijzer' }}"><span data-weather-icon aria-hidden="true">☀</span><span data-weather-temperature>—°</span></a>
<div class="lang site-language" role="group" aria-label="Language / Taal">
@if($home)
<button type="button" data-lang="nl" aria-pressed="true">NL</button><button type="button" data-lang="en" aria-pressed="false">EN</button>
@else
<a href="{{ $seo['languagePaths']['nl'] ?? request()->fullUrlWithQuery(['lang'=>'nl']) }}" @if(!$en) aria-current="true" @endif>NL</a><a href="{{ $seo['languagePaths']['en'] ?? request()->fullUrlWithQuery(['lang'=>'en']) }}" @if($en) aria-current="true" @endif>EN</a>
@endif
</div></div></nav></header>
<script nonce="{{ request()->attributes->get('csp_nonce') }}" src="/js/site-navigation.js?v={{ filemtime(public_path('js/site-navigation.js')) }}" defer></script>

<script nonce="{{ request()->attributes->get('csp_nonce') }}" src="/js/weather.js?v={{ filemtime(public_path('js/weather.js')) }}" defer></script>
