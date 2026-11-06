@extends('community.layout')
@section('title', app()->getLocale()==='en'?'My moments — Jouw Lichtpuntje':'Mijn momentjes — Jouw Lichtpuntje')
@push('page-styles')<link rel="stylesheet" href="/css/moment-studio.css?v={{ filemtime(public_path('css/moment-studio.css')) }}"><script src="/js/moment-studio.js?v={{ filemtime(public_path('js/moment-studio.js')) }}" defer></script>@endpush
@section('content')
@php($en=app()->getLocale()==='en')
<div class="moments-studio">
<div class="moments-page-heading"><div><h1>{{ $en?'My moments':'Mijn momentjes' }}</h1></div><span class="moments-heading-sun" aria-hidden="true"></span></div>
<div class="moments-desk">
<section class="moment-writing-paper" id="new-moment" aria-labelledby="new-moment-heading"><span class="moment-pin" aria-hidden="true"></span><p class="moment-paper-label">{{ $en?'New moment':'Nieuw momentje' }}</p><h2 id="new-moment-heading">{{ $en?'What made your day brighter?':'Wat maakte jouw dag lichter?' }}</h2>
@if(auth()->user()->hasVerifiedEmail())@include('community.moment-form')@else<p><a href="/email/verify">{{ $en?'Confirm your email to join in.':'Bevestig eerst je e-mailadres om mee te doen.' }}</a></p>@endif
</section>
<section class="moment-keepsakes" id="moments-list" aria-labelledby="saved-moment-heading"><div class="moment-keepsakes-heading"><h2 id="saved-moment-heading">{{ $en?'This made you smile':'Dit maakte jou blij' }}</h2><div class="moment-month-nav"><a href="/mijn-momentjes?month={{ $month->subMonth()->format('Y-m') }}&amp;lang={{ app()->getLocale() }}#moments-list" aria-label="{{ $en?'Previous month':'Vorige maand' }}">‹</a><strong>{{ $month->locale($en?'en':'nl')->translatedFormat('F Y') }}</strong><a href="/mijn-momentjes?month={{ $month->addMonth()->format('Y-m') }}&amp;lang={{ app()->getLocale() }}#moments-list" aria-label="{{ $en?'Next month':'Volgende maand' }}">›</a></div></div>
@if($selectedDay)<p class="small">{{ $en?'Selected day: ':'Gekozen dag: ' }}{{ \Carbon\CarbonImmutable::parse($selectedDay)->format('d-m-Y') }} · <a href="/mijn-momentjes?month={{ $month->format('Y-m') }}&amp;lang={{ app()->getLocale() }}#moments-list">{{ $en?'Show the whole month':'Toon de hele maand' }}</a></p>@endif
<div class="moment-keepsake-grid">
@forelse($moments as $moment)
<article class="moment-keepsake" id="moment-{{ $moment->id }}"><span class="moment-pin" aria-hidden="true"></span><time datetime="{{ $moment->moment_date->toDateString() }}">{{ $moment->moment_date->format('d-m-Y') }}</time>
@include('shared.moment-image',['momentImageContext'=>'account'])
<p class="moment-keepsake-text">{{ $moment->bodyFor() }}</p>
<div class="moment-labels"><span>{{ $moment->visibility==='private'?($en?'Private':'Privé'):($en?'Shared':'Gedeeld') }}</span>@if($moment->status!=='approved')<span class="moment-label-pending">{{ ['pending'=>$en?'Awaiting review':'Wacht op controle','rejected'=>$en?'Please adjust':'Graag aanpassen','doubt'=>$en?'Review uncertain':'Controle onduidelijk'][$moment->status]??$moment->status }}</span>@endif</div>
@if($moment->status!=='approved' && $moment->reason)<p class="small">{{ $moment->reason[$en?'en':'nl']??'' }}</p>@endif
@if($moment->image_accepted_at && !$moment->hasMomentImage())<p class="moment-image-status">{{ $moment->image_state==='failed'?($en?'Image unavailable. You can retry below.':'Afbeelding niet gelukt. Je kunt hieronder opnieuw proberen.'):($en?'Your image will appear after review and when allowance is available.':'Je beeld verschijnt na controle en zodra er beeldtegoed is.') }}</p>@endif
<details class="moment-edit"><summary>{{ $en?'Edit or manage':'Aanpassen of beheren' }}</summary>@include('community.moment-form',['editing'=>$moment])
@if($moment->status==='approved' && !$moment->hasMomentImage() && !in_array($moment->image_state,['queued','processing','waiting'],true))<form method="post" action="/momentjes/{{ $moment->id }}/afbeelding?lang={{ app()->getLocale() }}">@csrf<label class="moment-consent"><input type="checkbox" name="image_processing" value="1" required><span>{{ $en?'I agree to Gemini choosing a subject and Cloudflare creating a small image, including when private.':'Ik ga akkoord dat Gemini een onderwerp kiest en Cloudflare een klein beeld maakt, ook als ik privé kies.' }}</span></label><button class="moment-cut-button" type="submit">{{ $en?'Create a little image':'Maak een klein beeld' }}</button></form>@endif
<form class="moment-delete-form" method="post" action="/momentjes/{{ $moment->id }}?lang={{ app()->getLocale() }}" data-confirm-moment="{{ $en?'Delete this moment?':'Dit momentje verwijderen?' }}">@csrf @method('DELETE')<button class="moment-delete-button" type="submit">{{ $en?'Delete moment':'Momentje verwijderen' }}</button></form></details>
</article>
@empty<div class="moment-keepsake moment-keepsake-empty"><img src="/images/share-choice-coffee.png" width="110" height="110" alt=""><h3>{{ $en?'Room for your little joys':'Ruimte voor jouw kleine gelukjes' }}</h3><p>{{ $en?'No moments this month yet. Your first moment will appear here. Reading something kind is welcome too.':'Nog geen momentjes deze maand. Je eerste momentje komt hier te hangen. Iets fijns lezen mag natuurlijk ook.' }}</p><a href="/momentjes?lang={{ app()->getLocale() }}">{{ $en?'Borrow a smile →':'Leen een glimlach →' }}</a></div>@endforelse
</div>
{{ $moments->links() }}
</section></div></div>
@endsection
