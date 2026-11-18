@if(empty($feed['articles']) && ($feed['nearby']??false))
<p class="empty">{{ $locale==='en'?'No bright spots are tagged with this town or region yet. Try “All bright spots” too.':'Er zijn nog geen lichtpuntjes met deze plaats of regio als label. Bekijk ook ‘Alle lichtpuntjes’.' }}</p>
@endif
@foreach($feed['articles'] as $story)
<article class="card" data-read="{{ $story['id'] }}"><div class="picture">
@if(\App\Support\StorySeo::safeUrl($story['photo']['url']??null))<img data-article-photo src="{{ $story['photo']['url'] }}" alt="{{ $story['photo']['description']??$story[$locale]['title'] }}" width="1024" height="576" loading="lazy">@endif
<span class="category">{{ \App\Support\StorySeo::CATEGORIES[$story['cat']][$locale==='en'?1:0] }}</span></div>
<div class="cardbody"><div class="card-metadata"><p class="meta">{{ $story['source'] }} @if($story['local']??false) · {{ $locale==='en'?'Nearby':'Uit jouw buurt' }} @endif · {{ $story['date'] }}</p>@include('shared.ai-image-label',['generated'=>$story['photo']['generated']??false])</div><h3><a class="card-title" href="{{ $story['story_urls'][$locale] }}" aria-haspopup="dialog" aria-controls="reader">{{ $story[$locale]['title'] }}</a></h3><a class="mobile-card-read card-title" href="{{ $story['story_urls'][$locale] }}" aria-haspopup="dialog" aria-controls="reader">{{ $locale==='en'?'Read this bright spot →':'Lees dit lichtpuntje →' }}</a><p class="teaser">{{ $story[$locale]['teaser']??'' }}</p><div class="card-actions"></div></div></article>
@if(($loop->iteration===3||($loop->last&&$loop->iteration<3))&&$category==='all'&&$feed['pagination']['current_page']===1)@include('homepage.moments')@endif
@endforeach
