@php
$en=app()->getLocale()==='en';
$current=$progress['current'];
$next=$progress['next'];
@endphp
@push('page-styles')<link rel="stylesheet" href="/css/badge-path.css?v={{ filemtime(public_path('css/badge-path.css')) }}"><script src="/js/badge-path.js?v={{ filemtime(public_path('js/badge-path.js')) }}" defer></script>@endpush
<section class="badge-path" aria-label="{{ $en?'Your badge collection':'Jouw badgeverzameling' }}">
<div class="badge-path-summary">
<div class="badge-path-total"><strong>{{ $progress['points'] }}</strong><span>{{ $en?'bright points':'lichtpunten' }}</span></div>
<div class="badge-path-count"><strong>{{ $progress['earned_count'] }} <span>{{ $en?'of':'van' }}</span> {{ $progress['total_badges'] }}</strong><span>{{ $en?'badges earned':'badges behaald' }}</span></div>
<div class="badge-path-current">@include('shared.paper-badge',['badge'=>$current])<div><span>{{ $en?'Your current badge':'Jouw huidige badge' }}</span><strong>{{ $current['name'] }}</strong></div></div>
@if($next)<div class="badge-path-next">@include('shared.paper-badge',['badge'=>$next])<div><span>{{ $en?'Next badge':'Volgende badge' }}</span><strong>{{ $next['name'] }}</strong><span>{{ $en?'Another ':'Nog ' }}<b>{{ $next['points']-$progress['points'] }}</b> {{ $en?'bright points':'lichtpunten' }}</span></div></div>@else<div class="badge-path-next"><strong>{{ $en?'Every badge earned!':'Alle badges behaald!' }}</strong><span>{{ $en?'Keep sharing a little light.':'Blijf een beetje licht doorgeven.' }}</span></div>@endif
<progress value="{{ $progress['progress'] }}" max="100" aria-label="{{ $en?'Progress towards your next badge':'Voortgang naar je volgende badge' }}">{{ $progress['progress'] }}%</progress>
</div>
<ol class="badge-path-grid">
@foreach($progress['stages'] as $stage)
@php
$earned=$stage['points']<=$progress['points'];
$isCurrent=$stage['id']===$current['id'];
$isNext=$next&&$stage['id']===$next['id'];
@endphp
<li class="badge-path-step {{ $earned?'is-earned':'is-locked' }} {{ $isCurrent?'is-current':'' }} {{ $isNext?'is-next':'' }}" @if($isCurrent)aria-current="step"@endif>
<span class="badge-path-number" aria-hidden="true">{{ str_pad((string)$loop->iteration,2,'0',STR_PAD_LEFT) }}</span>
<div class="badge-path-disc">@include('shared.paper-badge',['badge'=>$stage])</div>
<h2>{{ $stage['name'] }}</h2><p>{{ $stage['points'] }} {{ $en?'bright points':'lichtpunten' }}</p>
<span class="badge-path-state">{{ $isCurrent?($en?'You are here':'Hier sta je'):($isNext?($en?'Next badge':'Volgende badge'):($earned?($en?'Unlocked':'Vrijgespeeld'):($en?'Still locked':'Nog op slot'))) }}</span>
<details class="badge-path-description"><summary>{{ $en?'About this badge':'Over deze badge' }}</summary><p>{{ $stage['description'] }}</p></details>
</li>
@endforeach
</ol>
</section>
