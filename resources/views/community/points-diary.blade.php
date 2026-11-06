@php
 $en=$profileEn;
 $diaryDays=$pointsDiary['days'];
 $today=now('Europe/Amsterdam')->toDateString();
 $yesterday=now('Europe/Amsterdam')->subDay()->toDateString();
@endphp
<link rel="stylesheet" href="/css/points-diary.css?v={{ filemtime(public_path('css/points-diary.css')) }}">
<section class="points-diary" aria-label="{{ $en?'Your bright points diary':'Jouw lichtpuntjesdagboek' }}">
 <div class="diary-intro"><p>{{ $en?'Little things, lovely together.':'Kleine dingen, mooi bij elkaar.' }} <span aria-hidden="true">♡</span></p><img src="/images/points-diary-sun-jar-v1.png" width="90" height="90" alt=""></div>
 <div class="diary-stats">
  <div class="diary-stat"><strong class="diary-stat-number">{{ $progress['points'] }}</strong><p>{{ $en?'bright points collected':'lichtpunten verzameld' }}</p></div>
  <div class="diary-stat"><p>{{ $en?'Today':'Vandaag' }}</p><strong class="diary-stat-number">{{ \App\Services\PointsDiary::signed($pointsDiary['today']) }}</strong><p>{{ $en?'bright points':'lichtpunten' }}</p></div>
  <div class="diary-stat diary-stat--badge"><div class="diary-badge-icon">@include('shared.paper-badge',['badge'=>$progress['current']])</div><div><p>{{ $en?'Your badge':'Jouw badge' }}</p><strong class="diary-badge-name">{{ $progress['current']['name'] }}</strong><a class="button diary-cut-button diary-cut-button--sage" href="/profiel?section=badges&amp;lang={{ $en?'en':'nl' }}">{{ $en?'View your badges':'Bekijk je badges' }} <span aria-hidden="true">→</span></a></div></div>
 </div>
 <div class="diary-board">
 @forelse($diaryDays as $day)
 @php
  $date=\Carbon\CarbonImmutable::createFromFormat('!Y-m-d',$day['period'],'Europe/Amsterdam')->locale($en?'en':'nl');
  $relative=$day['period']===$today?($en?'Today':'Vandaag'):($day['period']===$yesterday?($en?'Yesterday':'Gisteren'):null);
 @endphp
 <article class="diary-day" data-diary-date="{{ $day['period'] }}" aria-labelledby="day-{{ $day['period'] }}">
  <div class="diary-date"><time id="day-{{ $day['period'] }}" datetime="{{ $day['period'] }}">@if($relative)<strong>{{ $relative }}</strong>@endif<span>{{ $date->translatedFormat('j F') }}</span>@if($date->year!==now('Europe/Amsterdam')->year)<span class="diary-year">{{ $date->year }}</span>@endif</time><span class="diary-date-flower" aria-hidden="true"></span></div>
  <ul class="diary-activities">
   @foreach($day['groups'] as $group)
   <li class="diary-activity {{ $group['active']?'':'diary-activity--withdrawn' }}">
    @include('community.points-diary-icon',['kind'=>$group['kind']])
    <div class="diary-activity-label"><span>{{ \App\Services\PointsDiary::label($group,$en) }}</span>@if(!$group['active'])<span class="diary-withdrawn-note">{{ $en?'Withdrawn · does not count':'Ingetrokken · telt niet mee' }}</span>@endif</div>
    <strong class="diary-activity-points">@if($group['active']){{ \App\Services\PointsDiary::signed($group['points']) }}@else<span aria-hidden="true">—</span><span class="diary-sr">{{ $en?'No points':'Geen punten' }}</span>@endif</strong>
   </li>
   @endforeach
  </ul>
  <div class="diary-day-total"><strong>{{ \App\Services\PointsDiary::signed($day['points']) }}</strong><span>{{ $en?'day total':'dagtotaal' }}</span></div>
 </article>
 @empty
 <div class="diary-empty"><img src="/images/points-diary-sun-jar-v1.png" width="120" height="120" alt=""><h2>{{ $en?'Your diary starts with a little sunshine':'Je dagboek begint met een lichtpuntje' }}</h2><p>{{ $en?'Read a lovely story or share a kind word. Your bright points will appear here, neatly together each day.':'Lees een fijn verhaal of laat een vriendelijk woord achter. Hier verschijnen je lichtpunten, netjes per dag bij elkaar.' }}</p><a class="button diary-cut-button" href="/?lang={{ $en?'en':'nl' }}">{{ $en?'Discover the stories':'Ontdek de lichtpuntjes' }} <span aria-hidden="true">→</span></a></div>
 @endforelse
 @if($diaryDays->hasPages())<nav class="diary-pagination" aria-label="{{ $en?'Browse diary days':'Bladeren door dagboekdagen' }}">
 @if($diaryDays->previousPageUrl())<a class="button diary-cut-button diary-cut-button--sage" href="{{ $diaryDays->previousPageUrl() }}">← {{ $en?'Newer days':'Nieuwere dagen' }}</a>@endif
 @if($diaryDays->hasMorePages())<a class="button diary-cut-button diary-cut-button--yellow" href="{{ $diaryDays->nextPageUrl() }}">{{ $en?'View earlier days':'Bekijk eerdere dagen' }} →</a>@endif
 </nav>@endif
 </div>
</section>
