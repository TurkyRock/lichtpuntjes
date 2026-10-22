@if(!empty($highlights)&&$category==='all'&&$feed['pagination']['current_page']===1)
<section class="daily-highlights" id="dailyHighlights" aria-label="{{ $locale==='en'?'Today’s bright spots':'Vandaag uitgelicht' }}">
@foreach($highlights as $highlight)
@php
    $story=$highlight['story'];
    $kind=$highlight['kind'];
    $labels=['daily'=>['Lichtpuntje van de dag','Bright spot of the day'],'featured'=>['Uitgelicht','Featured'],'reads'=>['Meest gelezen · 7 dagen','Most read · 7 days'],'likes'=>['Meeste likes · 7 dagen','Most liked · 7 days'],'likes_total'=>['Meeste likes','Most liked'],'discover'=>['Om te ontdekken','Discover a bright spot']];
@endphp
<article data-feature-card="{{ $story['id'] }}" class="highlight-card {{ $loop->first?'highlight-main':'highlight-small' }}">
<div class="highlight-art"><a href="{{ $story['story_urls'][$locale] }}" data-feature-story="{{ $story['id'] }}" tabindex="-1" aria-hidden="true"><img data-article-photo src="{{ $story['photo']['url'] }}" alt="" width="1024" height="576" @if($loop->first)fetchpriority="high"@else loading="lazy"@endif></a>
@if($loop->first)<span class="category">{{ \App\Support\StorySeo::CATEGORIES[$story['cat']][$locale==='en'?1:0] }}</span>@endif
</div>
<div class="highlight-copy"><div class="card-metadata"><p class="highlight-label"><span aria-hidden="true">{{ in_array($kind,['daily','featured'])?'☀':(in_array($kind,['likes','likes_total'])?'♡':($kind==='reads'?'▤':'✦')) }}</span>{{ $labels[$kind][$locale==='en'?1:0] }}</p>@include('shared.ai-image-label',['generated'=>$story['photo']['generated']??false])</div>
<h2><a href="{{ $story['story_urls'][$locale] }}" data-feature-story="{{ $story['id'] }}" aria-haspopup="dialog" aria-controls="reader">{{ $story[$locale]['title'] }}</a></h2>
@if($loop->first&&!empty($story[$locale]['body']))
@php
    $fullText=implode("\n\n",$story[$locale]['body']);
    $wordCount=count(preg_split('/\s+/u',trim($fullText),-1,PREG_SPLIT_NO_EMPTY));
    $previewText=\Illuminate\Support\Str::words($fullText,max(1,(int)ceil($wordCount/2)),'…');
@endphp
<div class="highlight-body"><p>{{ $previewText }}</p></div>
@else
<p class="highlight-teaser">{{ $story[$locale]['teaser']??'' }}</p>
@endif
@if($kind==='likes_total')<p class="meta">♡ {{ $story['likes_count'] }} {{ $locale==='en'?'likes in total':'likes in totaal' }}</p>@endif<a class="highlight-read" href="{{ $story['story_urls'][$locale] }}" data-feature-story="{{ $story['id'] }}" aria-haspopup="dialog" aria-controls="reader">{{ $locale==='en'?'Read this bright spot →':'Lees dit lichtpuntje →' }}</a></div>
</article>
@endforeach
</section>
@endif
