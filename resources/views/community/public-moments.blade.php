@extends('community.layout')
@section('title', app()->getLocale()==='en'?'A smile from each other — Jouw Lichtpuntjes':'Een glimlach van elkaar — Jouw Lichtpuntjes')
@push('page-styles')<link rel="stylesheet" href="/css/moment-studio.css?v={{ filemtime(public_path('css/moment-studio.css')) }}"><script src="/js/moment-washing-line.js?v={{ filemtime(public_path('js/moment-washing-line.js')) }}" defer></script>@endpush
@section('content')
@php($en=app()->getLocale()==='en')
<div class="moment-gallery"><div class="moments-page-heading"><div><h1>{{ $en?'A smile from each other':'Een glimlach van elkaar' }}</h1><p>{{ $moments->isEmpty() ? ($en ? 'A place to share the little joys of everyday life.' : 'Een plek voor de kleine gelukjes van alledag.') : ($en ? 'Choose a card and borrow a smile.' : 'Klik op een kaartje en leen een glimlach.') }}</p></div>@if($moments->isNotEmpty())<a class="moment-cut-button" href="/lichtpuntje/delen?lang={{ app()->getLocale() }}">{{ $en?'Share a little joy':'Deel een momentje' }} ↗</a>@endif</div>
<div class="polaroid-board">
@forelse($moments as $moment)
<article class="moment-polaroid" id="moment-{{ $moment->id }}"><details class="moment-note-more polaroid-entry"><summary data-moment-open aria-controls="momentDetail">
@if($moment->hasMomentImage())@include('shared.moment-image',['momentImageContext'=>'polaroid'])@else<span class="polaroid-symbol" aria-hidden="true"><img src="/images/account-paper-sun.svg" width="85" height="85" alt=""></span>@endif
<span class="polaroid-preview-text">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u',' ',trim($moment->bodyFor())),110) }}</span><span class="polaroid-read-more">{{ $en?'Read this moment →':'Lees dit momentje →' }}</span></summary>
<div class="moment-full-content">@include('shared.moment-image',['momentImageContext'=>'detail'])<p class="moment-detail-text">{{ $moment->bodyFor() }}</p><p class="moment-detail-author">{{ $moment->user->name }} · {{ $moment->moment_date->format('d-m-Y') }}</p>@include('shared.moment-heart')@include('shared.moment-image-info')</div></details>
<div class="polaroid-footer"><div class="polaroid-author">@if($moment->user->avatarUrl())<img class="member-photo" src="{{ $moment->user->avatarUrl() }}" width="27" height="27" loading="lazy" alt="">@endif<div><strong>{{ $moment->user->name }}</strong><small>{{ $moment->moment_date->format('d-m-Y') }}</small></div></div>@include('shared.moment-heart')</div><a class="small" href="/melden/moment/{{ $moment->id }}?lang={{ app()->getLocale() }}">{{ app()->getLocale()==='en'?'Report':'Melden' }}</a></article>
@empty
<section class="moment-gallery-empty">
<h2>{{ $en ? 'What made you smile today?' : 'Wat maakte jouw dag vandaag nét iets lichter?' }}</h2>
<p>{{ $en ? 'A kind message, a walk or a cup of coffee in the sun. Your little joy can be the first one on the board.' : 'Een lief berichtje, een ommetje of koffie in de zon. Jouw kleine gelukje kan als eerste op het prikbord.' }}</p>
<a class="moment-cut-button" href="/mijn-momentjes?lang={{ app()->getLocale() }}">{{ $en ? 'Share a little joy' : 'Deel een momentje' }}</a>
</section>
@endforelse
</div>{{ $moments->links() }}</div>
<dialog id="momentDetail" class="moment-detail-dialog" aria-labelledby="momentDetailTitle"><div class="moment-detail-heading"><h2 id="momentDetailTitle">{{ $en?'A little joy to share':'Een klein gelukje om te delen' }}</h2><button type="button" class="close" data-close-moment aria-label="{{ $en?'Close moment':'Momentje sluiten' }}">×</button></div><div id="momentDetailBody"></div></dialog>
@endsection
