@foreach($feed['articles'] as $story)
<article class="card" data-read="{{ $story['id'] }}"><div class="picture">
@if(\App\Support\StorySeo::safeUrl($story['photo']['url']??null))<img data-article-photo src="{{ $story['photo']['url'] }}" alt="{{ $story['photo']['description']??$story[$locale]['title'] }}" width="1024" height="576" loading="lazy">@endif
<span class="category">{{ \App\Support\StorySeo::CATEGORIES[$story['cat']][$locale==='en'?1:0] }}</span></div>
<div class="cardbody"><div class="card-metadata"><p class="meta">{{ $story['source'] }} · {{ $story['date'] }}</p>@include('shared.ai-image-label',['generated'=>$story['photo']['generated']??false])</div><h3><a class="card-title" href="{{ $story['story_urls'][$locale] }}" aria-haspopup="dialog" aria-controls="reader">{{ $story[$locale]['title'] }}</a></h3><p class="teaser">{{ $story[$locale]['teaser']??'' }}</p><div class="card-actions"></div></div></article>
@if(($loop->iteration===3||($loop->last&&$loop->iteration<3))&&$category==='all'&&$feed['pagination']['current_page']===1)@include('homepage.moments')@endif
@endforeach
