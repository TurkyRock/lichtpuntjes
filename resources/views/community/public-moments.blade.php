@extends('community.layout')
@section('title', app()->getLocale()==='en'?'A smile from each other — Jouw Lichtpuntje':'Een glimlach van elkaar — Jouw Lichtpuntje')
@push('page-styles')<link rel="stylesheet" href="/css/moment-studio.css?v={{ filemtime(public_path('css/moment-studio.css')) }}"><script src="/js/moment-washing-line.js?v={{ filemtime(public_path('js/moment-washing-line.js')) }}" defer></script>@endpush
@section('content')
@php($en=app()->getLocale()==='en')
<div class="moment-gallery"><div class="moments-page-heading"><div><h1>{{ $en?'A smile from each other':'Een glimlach van elkaar' }}</h1><p>{{ $en?'Little joys, shared with you. Choose a card and borrow a smile.':'Kleine gelukjes, met jou gedeeld. Klik op een kaartje en leen een glimlach.' }}</p></div><a class="moment-cut-button" href="/lichtpuntje/delen?lang={{ app()->getLocale() }}">{{ $en?'Share a little joy':'Deel een momentje' }} ↗</a></div>
<div class="polaroid-board">
@forelse($moments as $moment)
<article class="moment-polaroid" id="moment-{{ $moment->id }}"><details class="moment-note-more polaroid-entry"><summary data-moment-open aria-controls="momentDetail">
@if($moment->hasMomentImage())@include('shared.moment-image',['momentImageContext'=>'polaroid'])@else<span class="polaroid-symbol" aria-hidden="true"><img src="/images/account-paper-sun.svg" width="85" height="85" alt=""></span>@endif
<span class="polaroid-preview-text">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u',' ',trim($moment->bodyFor())),110) }}</span><span class="polaroid-read-more">{{ $en?'Read this moment →':'Lees dit momentje →' }}</span></summary>
<div class="moment-full-content">@include('shared.moment-image',['momentImageContext'=>'detail'])<p class="moment-detail-text">{{ $moment->bodyFor() }}</p><p class="moment-detail-author">{{ $moment->user->name }} · {{ $moment->moment_date->format('d-m-Y') }}</p>@include('shared.moment-heart')<p class="small">{{ $moment->hasMomentImage()?($en?'AI-generated symbolic mood image.':'AI-gegenereerd symbolisch sfeerbeeld.') : '' }}</p></div></details>
<div class="polaroid-footer"><div class="polaroid-author">@if($moment->user->avatarUrl())<img class="member-photo" src="{{ $moment->user->avatarUrl() }}" width="27" height="27" loading="lazy" alt="">@endif<div><strong>{{ $moment->user->name }}</strong><small>{{ $moment->moment_date->format('d-m-Y') }}</small></div></div>@include('shared.moment-heart')</div></article>
@empty<p class="moment-gallery-empty">{{ $en?'The first moments are on their way. Yours could be the first little joy here.':'De eerste momentjes komen eraan. Misschien hangt jouw kleine gelukje hier straks als eerste.' }}</p>@endforelse
</div>{{ $moments->links() }}</div>
<dialog id="momentDetail" class="moment-detail-dialog" aria-labelledby="momentDetailTitle"><div class="moment-detail-heading"><h2 id="momentDetailTitle">{{ $en?'A little joy to share':'Een klein gelukje om te delen' }}</h2><button type="button" class="close" data-close-moment aria-label="{{ $en?'Close moment':'Momentje sluiten' }}">×</button></div><div id="momentDetailBody"></div></dialog>
@endsection
