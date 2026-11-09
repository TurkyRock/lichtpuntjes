@isset($momentHighlights)
<div class="moment-home-grid moment-paper-strip moment-washing-line">
<section class="moment-home" aria-label="{{ $locale==='en'?'Little moments from our community':'Kleine momentjes van elkaar' }}">
<div class="moment-strip-heading">
<span class="moment-paper-sun" aria-hidden="true"></span><span class="moment-botanical" aria-hidden="true"></span>
<div><h2>{{ $locale==='en'?'A smile from each other':'Een glimlach van elkaar' }}</h2><p>{{ $locale==='en'?'Small joys, shared with you.':'Kleine gelukjes, met jou gedeeld.' }}</p></div>
<a class="moment-more" href="/momentjes?lang={{ $locale }}">{{ $locale==='en'?'Read more moments →':'Meer momentjes lezen →' }}</a>
</div>
<div class="moment-line-content">
@if($momentHighlights->isNotEmpty())
<div class="moment-quotes">
@foreach($momentHighlights as $moment)
<article class="moment-paper-note" id="moment-{{ $moment->id }}">
<span class="moment-clothespin" aria-hidden="true"></span><span class="moment-clothespin second" aria-hidden="true"></span>
@php($momentSummary=preg_replace('/\s+/u',' ',trim($moment->bodyFor($locale))))
@php($momentLong=mb_strlen($momentSummary)>120)
<div class="moment-note-copy"><p class="moment-quote">{{ $momentLong?rtrim(mb_substr($momentSummary,0,119)).'…':$momentSummary }}</p>
<details class="moment-note-more"><summary data-moment-open aria-controls="momentDetail">{{ $locale==='en'?'Read the whole moment':'Lees het hele momentje' }} →</summary><div class="moment-full-content">@include('shared.moment-image',['momentImageContext'=>'detail'])<p class="moment-detail-text">{{ $moment->bodyFor($locale) }}</p><p class="moment-detail-author">{{ $moment->user->name }} · {{ $moment->moment_date->format('d-m-Y') }}</p>@include('shared.moment-heart')@if($moment->hasMomentImage())<p class="small">{{ $locale==='en'?'AI-generated symbolic mood image.':'AI-gegenereerd symbolisch sfeerbeeld.' }}</p>@endif</div></details></div>
<div class="moment-note-footer">
<div class="moment-author">
<span class="moment-avatar-stamp" aria-hidden="true"><span class="moment-avatar-window"><span class="moment-avatar-initial">{{ mb_strtoupper(mb_substr($moment->user->name,0,1)) }}</span>@if($moment->user->avatarUrl())<img class="member-photo" data-moment-avatar src="{{ $moment->user->avatarUrl() }}" width="35" height="41" loading="lazy" alt="">@endif</span></span>
<div class="moment-author-copy"><strong>{{ $moment->user->name }}</strong><small>{{ $moment->moment_date->format('d-m-Y') }}</small></div>
</div>
<div class="moment-note-reactions">@include('shared.moment-image',['momentImageContext'=>'home'])@include('shared.moment-heart')</div>
</div>
</article>
@endforeach
</div>
@else
<div class="moment-empty-hanging"><span class="moment-clothespin" aria-hidden="true"></span><span class="moment-clothespin second" aria-hidden="true"></span><p class="moment-strip-empty">{{ $locale==='en'?'A kind gesture, a shared laugh or a quiet moment. Yours could be the first little joy here.':'Een lief gebaar, samen lachen of even rust. Misschien staat jouw kleine gelukje hier straks als eerste.' }}</p></div>
@endif
</div>
</section>
<div class="moment-strip-footer">
<div class="moment-footer-copy">@if($showMomentPrompt)<aside class="moment-evening"><span class="moment-sun" aria-hidden="true">☀</span><div><h2>{{ $locale==='en'?'Close your day with something lovely':'Sluit je dag af met iets moois' }}</h2><p>{{ $locale==='en'?'A small moment counts too. Save it for yourself or share a smile.':'Iets kleins telt ook. Bewaar het voor jezelf of geef een glimlach door.' }}</p></div></aside>@endif</div>
<div class="moment-footer-actions">
<a class="moment-paper-action" data-account-link href="/lichtpuntje/delen?lang={{ $locale }}">{{ $locale==='en'?'Share a little moment':'Deel een momentje' }} →</a>
@if($showMomentPrompt)<a class="moment-paper-action moment-paper-action-green" data-account-link href="/lichtpuntje/delen?lang={{ $locale }}">{{ $locale==='en'?'Share a bright spot':'Deel een lichtpuntje' }} →</a>@endif
</div></div>
</div>
@endisset
