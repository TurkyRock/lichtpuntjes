@php($en = app()->getLocale() === 'en')
@php($home = $home ?? false)
<header class="site-header"><nav class="site-nav" aria-label="{{ $en ? 'Main navigation' : 'Hoofdmenu' }}">
<a href="/?lang={{ $en ? 'en' : 'nl' }}" class="site-brand" aria-label="Lichtpunt home"><span class="site-sun" aria-hidden="true">☼</span><span>Lichtpunt<span class="site-dot">.</span></span></a>
<div class="site-links">
<a class="site-text" href="{{ $home ? '#about' : '/?lang='.($en ? 'en' : 'nl').'#about' }}" data-nav-nl="Ons verhaal" data-nav-en="Our story">{{ $en ? 'Our story' : 'Ons verhaal' }}</a>
<a class="site-text" data-account-link href="/lichtpuntje/schrijven?lang={{ $en ? 'en' : 'nl' }}" @if(request()->is('lichtpuntje/schrijven')) aria-current="page" @endif><svg class="site-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/></svg><span data-nav-nl="Schrijf een lichtpuntje" data-nav-en="Write a bright spot">{{ $en ? 'Write a bright spot' : 'Schrijf een lichtpuntje' }}</span></a>
@auth
@if(auth()->user()->is_admin)
<a class="site-text" data-account-link href="/beheer?lang={{ $en ? 'en' : 'nl' }}" data-nav-nl="Beheer" data-nav-en="Admin">{{ $en ? 'Admin' : 'Beheer' }}</a>
@endif
<a class="site-text" data-account-link href="/profiel?lang={{ $en ? 'en' : 'nl' }}" @if(request()->is('profiel')) aria-current="page" @endif ><svg class="site-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3Z"/></svg><span data-nav-nl="Mijn account" data-nav-en="My account">{{ $en ? 'My account' : 'Mijn account' }}</span></a>
<form method="post" action="/uitloggen" class="site-logout">
@csrf
<button type="submit" class="site-text" ><svg class="site-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 3H3v18h6M10 12h11m-4-4 4 4-4 4"/></svg><span data-nav-nl="Uitloggen" data-nav-en="Log out">{{ $en ? 'Log out' : 'Uitloggen' }}</span></button></form>
@else
<a class="site-text" data-account-link href="/aanmelden?lang={{ $en ? 'en' : 'nl' }}" ><svg class="site-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="7" r="4"/><path d="M2 21v-3a7 7 0 0 1 12-5M19 12v8m-4-4h8"/></svg><span data-nav-nl="Registreren" data-nav-en="Register">{{ $en ? 'Register' : 'Registreren' }}</span></a>
<a class="site-text" data-account-link href="/inloggen?lang={{ $en ? 'en' : 'nl' }}" ><svg class="site-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3Z"/></svg><span data-nav-nl="Inloggen" data-nav-en="Log in">{{ $en ? 'Log in' : 'Inloggen' }}</span></a>
@endauth
<div class="lang site-language" role="group" aria-label="Language / Taal">
@if($home)
<button type="button" data-lang="nl" aria-pressed="true">NL</button><button type="button" data-lang="en" aria-pressed="false">EN</button>
@else
<a href="{{ request()->fullUrlWithQuery(['lang'=>'nl']) }}" @if(!$en) aria-current="true" @endif>NL</a><a href="{{ request()->fullUrlWithQuery(['lang'=>'en']) }}" @if($en) aria-current="true" @endif>EN</a>
@endif
</div></div></nav></header>
