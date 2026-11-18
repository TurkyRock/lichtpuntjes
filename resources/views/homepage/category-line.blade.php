@once
<link rel="stylesheet" href="/css/personal-news.css?v={{ filemtime(public_path('css/personal-news.css')) }}">
@endonce
@php($hasNewsPreferences=app(\App\Services\NewsPreferences::class)->enabled(auth()->user()))
<div class="personal-news-switch">
@if(auth()->check())
@php($needsNewsPreferences=!$hasNewsPreferences && !auth()->user()->news_preferences_answered_at)
<div class="personal-news-choices">
<nav aria-label="{{ $locale==='en'?'News selection':'Nieuwsselectie' }}">
@php($personalView=($feed['personalized']??false))
@php($nearbyView=($feed['nearby']??false))
@php($hasLocation=$hasNewsPreferences && \App\Support\StoryLocation::places(auth()->user()->news_preferences['place']??null,auth()->user()->news_preferences['region']??null))
<a href="{{ $hasNewsPreferences ? \App\Support\StorySeo::home($locale,$category).'?view=for-you' : '/mijn-interesses?welcome=1&lang='.$locale }}" data-news-view="for-you" @if($personalView) aria-current="page" @endif>{{ $locale==='en'?'For you':'Voor jou' }}</a>
<a href="{{ $hasLocation ? \App\Support\StorySeo::home($locale,$category).'?view=nearby' : '/mijn-interesses?lang='.$locale.'#preferencePlace' }}" data-news-view="nearby" @if($nearbyView) aria-current="page" @endif>{{ $locale==='en'?'Nearby':'Uit de buurt' }}</a>
<a href="{{ \App\Support\StorySeo::home($locale,$category) }}?view=all" data-news-view="all" @if(!$personalView && !$nearbyView) aria-current="page" @endif>{{ $locale==='en'?'All bright spots':'Alle lichtpuntjes' }}</a>
<a class="news-preference-edit{{ $needsNewsPreferences?' interests-unset':'' }}" href="/mijn-interesses?{{ $needsNewsPreferences?'welcome=1&':'' }}lang={{ $locale }}"><span aria-hidden="true">✦</span> {{ !$needsNewsPreferences?($locale==='en'?'My interests':'Mijn interesses'):($locale==='en'?'Choose your interests':'Kies je interesses') }}</a>
</nav>
<p id="personalNewsExplanation">{{ $needsNewsPreferences ? ($locale==='en'?'Choose what makes you happy.':'Kies wat jou blij maakt.') : ($nearbyView ? ($locale==='en'?'News and community stories from your chosen town or province.':'Nieuws en zelfgeschreven verhalen uit jouw gekozen plaats of provincie.') : ($personalView ? ($locale==='en'?'Your interests and nearby stories, with room for discoveries.':'Jouw interesses en verhalen uit de buurt, met ruimte voor verrassing.') : ($locale==='en'?'All topics, in the selected order.':'Alle onderwerpen, in de gekozen volgorde.'))) }}</p>
</div>
@endif
<a class="home-weather-invitation" href="/weer?lang={{ $locale }}"><img src="/images/site-paper-sun.svg" width="44" height="44" alt=""><span><strong>{{ $locale==='en'?'Heading outside?':'Even naar buiten?' }}</strong><span>{{ $locale==='en'?'Find your sunny moment →':'Vind jouw zonnige moment →' }}</span></span></a>
</div>
@php($selectedSort=in_array(request('sort'),['reads','likes'],true)?request('sort'):'newest')
<div class="news-toolbar category-clothesline" role="region" aria-label="{{ $locale==='en'?'Choose a category and sort the news':'Kies een categorie en sorteer het nieuws' }}">
<h2 class="mobile-news-heading">{{ $locale==='en'?'Discover bright spots':'Ontdek lichtpuntjes' }}</h2>
<div class="mobile-topic-control"><label class="daily-light-sr" for="mobileTopic">{{ $locale==='en'?'Choose a topic':'Kies een onderwerp' }}</label><select id="mobileTopic" name="category">
@foreach(\App\Support\StorySeo::CATEGORIES as $key=>$label)
<option value="{{ $key }}" @selected($category===$key)>{{ $key==='all'?($locale==='en'?'Topic: All':'Onderwerp: Alles'):$label[$locale==='en'?1:0] }}</option>
@endforeach
</select></div>
<div class="topic-control"><div class="filters" id="filters" role="group" aria-label="{{ $locale==='en'?'Categories':'Categorieën' }}">
@foreach(\App\Support\StorySeo::CATEGORIES as $key=>$label)
<a data-cat="{{ $key }}" href="{{ \App\Support\StorySeo::home($locale,$key).'?'.http_build_query(array_filter(['sort'=>$selectedSort==='newest'?null:$selectedSort,'view'=>request('view')==='all'?'all':null])) }}" @if($category===$key) aria-current="page" @endif><span class="category-peg" aria-hidden="true"></span><svg class="filter-icon" viewBox="0 0 32 32" aria-hidden="true"><use href="/images/home-filter-icons.svg#{{ $key }}"/></svg><span class="category-label">{{ $label[$locale==='en'?1:0] }}</span><small class="category-chosen">✓ {{ $locale==='en'?'Selected':'Gekozen' }}</small></a>
@endforeach
</div></div>
<div class="sort-control"><span class="category-peg" aria-hidden="true"></span><label for="newsSort" data-i="sortLabel">{{ $locale==='en'?'Sort by':'Sorteer op' }}</label><div class="sort-tabs" id="sortTabs"><select id="newsSort" name="sort">@foreach(['newest'=>['Nieuwste','Latest'],'reads'=>['Meest gelezen','Most read'],'likes'=>['Meeste likes','Most liked']] as $key=>$label)<option value="{{ $key }}" @selected($selectedSort===$key)>{{ $label[$locale==='en'?1:0] }}</option>@endforeach</select><svg viewBox="0 0 20 14" aria-hidden="true"><path d="m2 2 8 8 8-8" fill="none" stroke="currentColor" stroke-width="3"/></svg></div></div>
</div>
