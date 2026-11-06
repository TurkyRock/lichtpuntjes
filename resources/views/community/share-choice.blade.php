@extends('community.layout')
@section('title', app()->getLocale()==='en'?'What would you like to share? — Jouw Lichtpuntje':'Wat wil jij delen? — Jouw Lichtpuntje')
@push('page-styles')
<link rel="stylesheet" href="/css/share-choice.css?v={{ filemtime(public_path('css/share-choice.css')) }}">
@endpush
@section('content')
@php($en=app()->getLocale()==='en')
<div class="share-choice-page">
    <header class="choice-heading">
        <span class="choice-sun" aria-hidden="true"></span>
        <h1>{{ $en?'What would you like to share?':'Wat wil jij delen?' }}</h1>
        <p>{{ $en?'A little joy or a bigger story: every bright spot counts.':'Klein geluk of een groter verhaal: ieder lichtpuntje telt.' }}</p>
    </header>

    <div class="choice-grid">
        <section class="choice-paper choice-moment" aria-labelledby="choice-moment-title">
            <span class="choice-tape choice-tape-left" aria-hidden="true"></span>
            <span class="choice-tape choice-tape-right" aria-hidden="true"></span>
            <h2 id="choice-moment-title">{{ $en?'A little moment':'Een momentje' }}</h2>
            <div class="choice-lead">
                <div>
                    <p>{{ $en?'A few sentences about something that made your day a little brighter.':'Een paar zinnen over iets dat je dag lichter maakte.' }}</p>
                    <ul class="choice-points">
                        <li>{{ $en?'Keep it private or share it':'Privé bewaren of om te delen' }}</li>
                        <li>{{ $en?'Short and simple, with an optional small image':'Klein en eenvoudig, met een optioneel klein beeld' }}</li>
                    </ul>
                </div>
                <img class="choice-illustration" src="/images/share-choice-coffee.png" width="1024" height="1024" alt="" aria-hidden="true" decoding="async">
            </div>
            <figure class="choice-example">
                <figcaption>{{ $en?'For example':'Bijvoorbeeld' }}</figcaption>
                <blockquote><p>{{ $en?'My neighbour brought me a cup of coffee, just because.':'Mijn buurvrouw zette zomaar een kop koffie voor me klaar.' }}</p></blockquote>
            </figure>
            <a class="choice-action" href="/mijn-momentjes?lang={{ $en?'en':'nl' }}#new-moment">{{ $en?'Write a moment':'Schrijf een momentje' }} <span aria-hidden="true">→</span></a>
        </section>

        <section class="choice-paper choice-story" aria-labelledby="choice-story-title">
            <span class="choice-tape choice-tape-left" aria-hidden="true"></span>
            <span class="choice-tape choice-tape-right" aria-hidden="true"></span>
            <h2 id="choice-story-title">{{ $en?'A story':'Een verhaal' }}</h2>
            <div class="choice-lead">
                <div>
                    <p>{{ $en?'Something lovely that deserves a little more space.':'Iets moois dat wat meer ruimte verdient.' }}</p>
                    <ul class="choice-points">
                        <li>{{ $en?'A title and a more detailed story':'Een titel en een uitgebreider verhaal' }}</li>
                        <li>{{ $en?'Shared publicly after review, with a matching AI image':'Na controle openbaar, met een passende AI-afbeelding' }}</li>
                    </ul>
                </div>
                <img class="choice-illustration" src="/images/share-choice-story.png" width="1024" height="1024" alt="" aria-hidden="true" decoding="async">
            </div>
            <figure class="choice-example">
                <figcaption>{{ $en?'For example':'Bijvoorbeeld' }}</figcaption>
                <blockquote><p>{{ $en?'How our neighbourhood created a little green square together':'Hoe onze buurt samen een groen pleintje maakte' }}</p></blockquote>
            </figure>
            <a class="choice-action" href="/lichtpuntje/schrijven?lang={{ $en?'en':'nl' }}">{{ $en?'Write a story':'Schrijf een verhaal' }} <span aria-hidden="true">→</span></a>
        </section>
    </div>
    <p class="choice-reassurance"><span aria-hidden="true">❧</span>{{ $en?'No pressure. You do not have to write something big to share something lovely.':'Geen druk. Je hoeft niet groot te schrijven om iets moois te delen.' }}<span aria-hidden="true">❧</span></p>
</div>
@endsection
