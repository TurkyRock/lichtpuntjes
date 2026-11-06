@php($selectedSort=in_array(request('sort'),['reads','likes'],true)?request('sort'):'newest')
<div class="news-toolbar category-clothesline" role="region" aria-label="{{ $locale==='en'?'Choose a category and sort the news':'Kies een categorie en sorteer het nieuws' }}">
<div class="topic-control"><div class="filters" id="filters" role="group" aria-label="{{ $locale==='en'?'Categories':'Categorieën' }}">
@foreach(\App\Support\StorySeo::CATEGORIES as $key=>$label)
<a data-cat="{{ $key }}" href="{{ \App\Support\StorySeo::home($locale,$key).($selectedSort==='newest'?'':'?sort='.$selectedSort) }}" @if($category===$key) aria-current="page" @endif><span class="category-peg" aria-hidden="true"></span><svg class="filter-icon" viewBox="0 0 32 32" aria-hidden="true"><use href="/images/home-filter-icons.svg#{{ $key }}"/></svg><span class="category-label">{{ $label[$locale==='en'?1:0] }}</span><small class="category-chosen">✓ {{ $locale==='en'?'Selected':'Gekozen' }}</small></a>
@endforeach
</div></div>
<div class="sort-control"><span class="category-peg" aria-hidden="true"></span><label for="newsSort" data-i="sortLabel">{{ $locale==='en'?'Sort by':'Sorteer op' }}</label><div class="sort-tabs" id="sortTabs"><select id="newsSort" name="sort">@foreach(['newest'=>['Nieuwste','Latest'],'reads'=>['Meest gelezen','Most read'],'likes'=>['Meeste likes','Most liked']] as $key=>$label)<option value="{{ $key }}" @selected($selectedSort===$key)>{{ $label[$locale==='en'?1:0] }}</option>@endforeach</select><svg viewBox="0 0 20 14" aria-hidden="true"><path d="m2 2 8 8 8-8" fill="none" stroke="currentColor" stroke-width="3"/></svg></div></div>
</div>
