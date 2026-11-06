@php
 $storyText=$seoArticle->{$locale}; $own=$seoArticle->origin==='community'; $en=$locale==='en';
 $hasPhoto=\App\Support\StorySeo::safeUrl($seoArticle->photo['url']??null);
 $isLiked=(bool)($seoStory['liked']??false);
@endphp
<div class="newspaper-story" data-newspaper-story>
<article class="newspaper-article">
<nav class="story-breadcrumb" aria-label="{{ $en?'Breadcrumb':'Kruimelpad' }}"><a href="{{ \App\Support\StorySeo::home($locale) }}">← {{ $en?'Back to bright spots':'Terug naar lichtpuntjes' }}</a><a class="news-category" href="{{ \App\Support\StorySeo::home($locale,$category) }}">{{ \App\Support\StorySeo::CATEGORIES[$category][$en?1:0] }}</a></nav>
<h1 class="newspaper-title" id="articleTitle">{{ $storyText['title'] }}</h1>
<div class="article-source">
@if($own)<span>{{ $en?'From our community':'Van onze community' }}</span><strong>{{ $seoArticle->source_name }}</strong>
@elseif(\App\Support\StorySeo::safeUrl($seoArticle->source_url))<a href="{{ $seoArticle->source_url }}" target="_blank" rel="noopener noreferrer">{{ $seoArticle->source_name }} ↗</a>
@else<strong>{{ $seoArticle->source_name }}</strong>
@endif
@if($seoArticle->published_at)<span aria-hidden="true">·</span><time datetime="{{ $seoArticle->published_at->toIso8601String() }}">{{ $seoArticle->published_at->format('d-m-Y') }}</time>@endif
</div>
<div class="newspaper-lead {{ $hasPhoto?'':'news-without-photo' }}">
@if($hasPhoto)
<figure class="article-photo newspaper-photo"><img src="{{ $seoArticle->photo['url'] }}" alt="{{ $seoArticle->photo['description']??$storyText['title'] }}" width="1024" height="576" data-article-photo>
@if(($seoArticle->photo['generated']??false)&&($seoArticle->photo['style']??'')!==\App\Services\LichtpuntIllustrationPrompt::STYLE)<figcaption class="photo-credit">{{ ($seoArticle->photo['style']??'')===\App\Services\LichtpuntIllustrationPrompt::PHOTO_STYLE?($en?'AI-generated atmosphere image · Not a photo of the reported event.':'AI-gegenereerd sfeerbeeld · Geen foto van de beschreven gebeurtenis.'):($en?'AI-generated illustration · Not a photo of the reported event.':'AI-gegenereerde illustratie · Geen foto van de beschreven gebeurtenis.') }}</figcaption>@endif
</figure>
@endif
<div class="newspaper-copy">@foreach($storyText['body'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach
@if(!$own && \App\Support\StorySeo::safeUrl($seoArticle->source_url))<div class="sourcebox"><a class="primary" href="{{ $seoArticle->source_url }}" target="_blank" rel="noopener noreferrer">{{ $en?'Read more at the source ↗':'Lees verder bij de bron ↗' }}</a></div>@endif
</div></div>
<div class="newspaper-ribbon"><div class="newspaper-engagement" data-engagement="{{ $seoArticle->id }}">
<button type="button" class="like-button newspaper-like" data-newspaper-like data-like="{{ $seoArticle->id }}" aria-pressed="{{ $isLiked?'true':'false' }}" aria-label="{{ $isLiked?($en?'Remove heart':'Hartje verwijderen'):($en?'Give a heart':'Geef een hartje') }}"><span class="newspaper-like-inner"><span class="newspaper-heart" aria-hidden="true">{{ $isLiked?'♥':'♡' }}</span><span>{{ $isLiked?($en?'Heart given':'Hartje gegeven'):($en?'Give a heart':'Geef een hartje') }}</span><span class="newspaper-number">{{ number_format((int)$seoArticle->likes_count,0,$en?'.':',',$en?',':'.') }}</span></span></button>
<span class="newspaper-views"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><span data-read-count aria-live="polite"><span class="newspaper-number">{{ number_format((int)$seoArticle->views_count,0,$en?'.':',',$en?',':'.') }}</span><span>{{ $en?'reads':'keer gelezen' }}</span></span></span>
</div><button type="button" class="share-story newspaper-share-toggle" data-share="{{ $seoArticle->id }}" aria-controls="shareOptions" aria-expanded="false">{{ $en?'Share this bright spot':'Deel dit lichtpuntje' }}</button></div>
</article>
@if(isset($relatedArticles)&&$relatedArticles->isNotEmpty())
<aside class="newspaper-related"><h3>{{ $en?'More bright spots to discover':'Meer lichtpuntjes om te ontdekken' }}</h3><div class="newspaper-related-grid">@foreach($relatedArticles as $related)<a class="newspaper-related-card" href="{{ \App\Support\StorySeo::path($related,$locale) }}" data-related-story="{{ $related->id }}">@if(\App\Support\StorySeo::safeUrl($related->photo['url']??null))<img src="{{ $related->photo['url'] }}" alt="" loading="lazy" width="100" height="82">@else<span class="related-paper-sun" aria-hidden="true">☀</span>@endif<span><strong>{{ $related->{$locale}['title'] }}</strong><span>{{ $en?'Read this bright spot →':'Lees dit lichtpuntje →' }}</span></span></a>@endforeach</div></aside>
@endif
<noscript><p class="newspaper-static-note">{{ $en?'You can read the story and follow the source link. Enable JavaScript to like, share or join the conversation.':'Je kunt het verhaal lezen en de bron openen. Zet JavaScript aan om een hartje te geven, te delen of mee te praten.' }}</p></noscript>
</div>
