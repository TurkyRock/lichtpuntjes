@if(!empty($highlights))
<section class="daily-highlights" id="dailyHighlights" aria-label="{{ $locale==='en'?'Today’s bright spots':'Vandaag uitgelicht' }}">
@foreach($highlights as $highlight)
@php
    $story=$highlight['story'];
    $kind=$highlight['kind'];
    $labels=['daily'=>['Lichtpuntje van de dag','Bright spot of the day'],'featured'=>['Uitgelicht','Featured'],'reads'=>['Meest gelezen · 7 dagen','Most read · 7 days'],'likes'=>['Meeste likes · 7 dagen','Most liked · 7 days'],'likes_total'=>['Meeste likes','Most liked'],'discover'=>['Om te ontdekken','Discover a bright spot']];
@endphp
<article data-feature-card="{{ $story['id'] }}" class="highlight-card highlight-kind-{{ $kind }} {{ $loop->first?'highlight-main':'highlight-small' }}">
<span class="highlight-sticker" aria-hidden="true">@if($loop->first)<img src="/images/site-paper-sun.svg" alt="" width="52" height="52">@elseif(in_array($kind,['likes','likes_total']))<svg viewBox="0 0 32 32"><use href="/images/home-filter-icons.svg#heart"/></svg>@else<img src="/images/highlight-paper-star.svg" alt="" width="40" height="40">@endif</span><span class="highlight-leaves" aria-hidden="true"></span><div class="highlight-art"><a href="{{ $story['story_urls'][$locale] }}" data-feature-story="{{ $story['id'] }}" tabindex="-1" aria-hidden="true"><img data-article-photo src="{{ $story['photo']['url'] }}" alt="" width="1024" height="576" @if($loop->first)fetchpriority="high"@else loading="lazy"@endif></a>
</div>
<div class="highlight-copy"><div class="card-metadata"><p class="highlight-label"><span aria-hidden="true">{{ in_array($kind,['daily','featured'])?'☀':(in_array($kind,['likes','likes_total'])?'♡':($kind==='reads'?'▤':'✦')) }}</span>{{ $labels[$loop->first?'daily':$kind][$locale==='en'?1:0] }}</p>@include('shared.ai-image-label',['generated'=>$story['photo']['generated']??false])</div>
<h2><a href="{{ $story['story_urls'][$locale] }}" data-feature-story="{{ $story['id'] }}" aria-haspopup="dialog" aria-controls="reader">{{ $story[$locale]['title'] }}</a></h2>
<p class="highlight-teaser">{{ $story[$locale]['teaser']??'' }}</p>
<a class="highlight-read" href="{{ $story['story_urls'][$locale] }}" data-feature-story="{{ $story['id'] }}" aria-haspopup="dialog" aria-controls="reader">{{ $locale==='en'?'Read this bright spot →':'Lees dit lichtpuntje →' }}</a></div>
</article>
@endforeach
</section>
@endif
