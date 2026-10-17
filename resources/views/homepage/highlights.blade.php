@if(!empty($highlights)&&$category==='all'&&$feed['pagination']['current_page']===1)
<section class="daily-highlights" id="dailyHighlights" aria-label="{{ $locale==='en'?'Today’s bright spots':'Vandaag uitgelicht' }}">
@foreach($highlights as $highlight)
@php($story=$highlight['story'])
@php($kind=$highlight['kind'])
@php($labels=['daily'=>['Lichtpuntje van de dag','Bright spot of the day'],'featured'=>['Uitgelicht','Featured'],'reads'=>['Meest gelezen · 7 dagen','Most read · 7 days'],'likes'=>['Meeste likes · 7 dagen','Most liked · 7 days'],'likes_total'=>['Meeste likes','Most liked'],'discover'=>['Om te ontdekken','Discover a bright spot']])
<article data-feature-card="{{ $story['id'] }}" class="highlight-card {{ $loop->first?'highlight-main':'highlight-small' }}">
<div class="highlight-art"><a href="{{ $story['story_urls'][$locale] }}" data-feature-story="{{ $story['id'] }}" tabindex="-1" aria-hidden="true"><img src="{{ $story['photo']['url'] }}" alt="" width="1024" height="576" @if($loop->first)fetchpriority="high"@else loading="lazy"@endif></a>
@if($loop->first)<span class="category">{{ \App\Support\StorySeo::CATEGORIES[$story['cat']][$locale==='en'?1:0] }}</span>@endif
@if(($story['photo']['generated']??false)&&($story['photo']['style']??'')!==\App\Services\LichtpuntIllustrationPrompt::STYLE)<div class="photo-credit">{{ ($story['photo']['style']??'')===\App\Services\LichtpuntIllustrationPrompt::PHOTO_STYLE?($locale==='en'?'AI-generated atmosphere image · Not a photo of the reported event.':'AI-gegenereerd sfeerbeeld · Geen foto van de beschreven gebeurtenis.'):($locale==='en'?'AI-generated illustration · Not a photo of the reported event.':'AI-gegenereerde illustratie · Geen foto van de beschreven gebeurtenis.') }}</div>@endif
</div>
<div class="highlight-copy"><p class="highlight-label"><span aria-hidden="true">{{ in_array($kind,['daily','featured'])?'☀':(in_array($kind,['likes','likes_total'])?'♡':($kind==='reads'?'▤':'✦')) }}</span>{{ $labels[$kind][$locale==='en'?1:0] }}</p>
<h2><a href="{{ $story['story_urls'][$locale] }}" data-feature-story="{{ $story['id'] }}" aria-haspopup="dialog" aria-controls="reader">{{ $story[$locale]['title'] }}</a></h2><p class="highlight-teaser">{{ $story[$locale]['teaser']??'' }}</p>@if($kind==='likes_total')<p class="meta">♡ {{ $story['likes_count'] }} {{ $locale==='en'?'likes in total':'likes in totaal' }}</p>@endif<a class="highlight-read" href="{{ $story['story_urls'][$locale] }}" data-feature-story="{{ $story['id'] }}" aria-haspopup="dialog" aria-controls="reader">{{ $locale==='en'?'Read this bright spot →':'Lees dit lichtpuntje →' }}</a></div>
</article>
@endforeach
</section>
@endif
