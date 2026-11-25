@php($dailyMoment=$momentHighlights->first())
<section class="daily-return-cards" aria-label="{{ $locale==='en'?'A little extra for today':'Een beetje extra voor vandaag' }}">
<article class="daily-return-card daily-return-weather">
<span class="daily-return-icon" aria-hidden="true">☁</span>
<div class="daily-return-copy">
<h2>{{ $locale==='en'?'Heading outside?':'Even naar buiten' }}</h2>
<p>{{ $locale==='en'?'Check the weather before you head out for a little walk.':'Bekijk het weer en kijk of er ruimte is voor een droog ommetje.' }}</p>
<a href="/weer?lang={{ $locale }}">{{ $locale==='en'?'See your local weather':'Bekijk jouw weer' }} →</a>
</div>
</article>
<article class="daily-return-card daily-return-moment{{ $dailyMoment?->user ? ' has-author' : '' }}">
<span class="daily-return-icon" aria-hidden="true">“</span>
<div class="daily-return-copy">
<h2>{{ $locale==='en'?'Today’s little moment':'Momentje van vandaag' }}</h2>
@if($dailyMoment)
<p>‘{{ \Illuminate\Support\Str::limit($dailyMoment->bodyFor($locale),140) }}’</p>
@else
<p>{{ $locale==='en'?'A kind gesture, a helpful hand or a lovely conversation.':'Een lief gebaar, een helpende hand of een fijn gesprek.' }}</p>
@endif
<a href="/momentjes?lang={{ $locale }}">{{ $locale==='en'?'Read the moments':'Lees de Momentjes' }} →</a>
</div>
@if($dailyMoment?->user)
<div class="daily-return-author">
@if($dailyMoment->user->avatarUrl())<img src="{{ $dailyMoment->user->avatarUrl() }}" width="30" height="30" loading="lazy" alt="">@else<span class="daily-return-author-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($dailyMoment->user->name,0,1)) }}</span>@endif
<span>{{ $dailyMoment->user->name }}</span>
</div>
@endif
</article>
</section>
