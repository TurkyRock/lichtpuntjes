@foreach($feed['articles'] as $story)
<article class="card" data-read="{{ $story['id'] }}"><div class="picture">
@if(\App\Support\StorySeo::safeUrl($story['photo']['url']??null))<img src="{{ $story['photo']['url'] }}" alt="{{ $story['photo']['description']??$story[$locale]['title'] }}" width="1024" height="576" loading="lazy">@endif
<span class="category">{{ \App\Support\StorySeo::CATEGORIES[$story['cat']][$locale==='en'?1:0] }}</span></div>
@if(($story['photo']['generated']??false)&&($story['photo']['style']??'')!==\App\Services\LichtpuntIllustrationPrompt::STYLE)<div class="photo-credit">{{ ($story['photo']['style']??'')===\App\Services\LichtpuntIllustrationPrompt::PHOTO_STYLE?($locale==='en'?'AI-generated atmosphere image · Not a photo of the reported event.':'AI-gegenereerd sfeerbeeld · Geen foto van de beschreven gebeurtenis.'):($locale==='en'?'AI-generated illustration · Not a photo of the reported event.':'AI-gegenereerde illustratie · Geen foto van de beschreven gebeurtenis.') }}</div>@endif
<div class="cardbody"><p class="meta">{{ $story['source'] }} · {{ $story['date'] }}</p><h3><a class="card-title" href="{{ $story['story_urls'][$locale] }}" aria-haspopup="dialog" aria-controls="reader">{{ $story[$locale]['title'] }}</a></h3><p class="teaser">{{ $story[$locale]['teaser']??'' }}</p><div class="card-actions"></div></div></article>
@endforeach
