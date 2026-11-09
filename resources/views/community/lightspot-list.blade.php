@extends('community.layout')
@section('title',app()->getLocale()==='en'?'My stories — Jouw Lichtpuntje':'Mijn verhalen — Jouw Lichtpuntje')
@push('page-styles')<link rel="stylesheet" href="/css/story-studio.css?v={{ filemtime(public_path('css/story-studio.css')) }}"><script src="/js/story-studio.js?v={{ filemtime(public_path('js/story-studio.js')) }}" defer></script>@endpush
@section('content')
@php
$en=app()->getLocale()==='en';
@endphp
<div class="story-studio">
<h1>{{ $en?'My stories':'Mijn verhalen' }}</h1>
@include('community.publication-revisions',['revisionScope'=>'stories'])
<div class="story-board">
<div class="story-write-column">
<section class="story-writing-paper" id="new-story" aria-labelledby="story-write-heading">
<span class="story-clothespin" aria-hidden="true"></span>
<h2 id="story-write-heading" class="story-yellow-label">{{ $en?'Your next bright spot':'Jouw volgende lichtpuntje' }}</h2>
<p class="story-write-intro">{{ $en?'Tell us about something that stayed with you. What made it special? Which smile would you like to pass on?':'Vertel over iets dat je bijbleef. Wat maakte het bijzonder? Welke glimlach wil je doorgeven?' }}</p>
@if(auth()->user()->hasVerifiedEmail()&&auth()->user()->community_accepted_at)
@include('community.story-form')
@else
<p>{{ $en?'Confirm your email address and accept the community rules in your account before submitting.':'Bevestig je e-mailadres en accepteer de huisregels in je account voordat je een verhaal instuurt.' }}</p><a class="story-cut-button" href="/profiel?lang={{ app()->getLocale() }}">{{ $en?'Go to my account':'Naar mijn account' }}</a>
@endif
</section>
<ol class="story-steps" aria-label="{{ $en?'From story to publication':'Van verhaal naar publicatie' }}"><li><span aria-hidden="true">1</span><div><strong>{{ $en?'Written':'Geschreven' }}</strong><small>{{ $en?'Your own words':'Jouw eigen woorden' }}</small></div></li><li><span aria-hidden="true">2</span><div><strong>{{ $en?'Review & AI image':'Controle & AI-beeld' }}</strong><small>{{ $en?'A fitting illustration':'Een passende illustratie' }}</small></div></li><li><span aria-hidden="true">3</span><div><strong>{{ $en?'Shared':'Gedeeld' }}</strong><small>{{ $en?'A smile to pass on':'Een glimlach om te delen' }}</small></div></li></ol>
</div>
<div class="story-collection-column">
<aside class="story-inspiration" aria-labelledby="story-inspiration-heading"><img src="/images/share-choice-coffee.png" alt="" width="88" height="88"><h2 id="story-inspiration-heading">{{ $en?'What would you like to tell?':'Waar wil jij over vertellen?' }}</h2><p>{{ $en?'Something that made you happy, however small. A few ideas to get started:':'Iets dat jou blij maakte, hoe klein ook. Een paar ideeën om je op weg te helpen:' }}</p><div class="story-prompts"><button type="button" data-story-prompt="encounter" aria-controls="story-inspiration-example"><span aria-hidden="true">♡</span>{{ $en?'An encounter':'Een ontmoeting' }}</button><button type="button" data-story-prompt="achievement" aria-controls="story-inspiration-example"><span aria-hidden="true">✦</span>{{ $en?'An achievement':'Iets bereikt' }}</button><button type="button" data-story-prompt="together" aria-controls="story-inspiration-example"><span aria-hidden="true">☀</span>{{ $en?'Something together':'Samen iets moois' }}</button></div><p id="story-inspiration-example" class="story-prompt-example" aria-live="polite" hidden></p></aside>
<section id="earlier-stories" class="story-collection" aria-labelledby="story-collection-heading"><h2 id="story-collection-heading">{{ ($needsAttention??false)?($en?'Stories to revise':'Verhalen om aan te passen'):($en?'Earlier stories':'Eerdere verhalen') }}</h2>
@if($needsAttention??false)<p><a href="/mijn-lichtpuntjes?lang={{ app()->getLocale() }}#earlier-stories">{{ $en?'Show all stories':'Alle verhalen bekijken' }}</a></p>@endif
@forelse($submissions as $s)
@php
$imageUrl=$s->status==='published'&&$s->article?->status==='published'?($s->article->photo['url']??null):null;
$imageUrl=is_string($imageUrl)&&preg_match('~^/news-images/[a-f0-9]{64}\.(?:jpg|png)$~D',$imageUrl)?$imageUrl:null;
$statusLabel=['pending'=>$en?'Awaiting review':'Wacht op controle','doubt'=>$en?'Administrator review':'Beheerder kijkt mee','rejected'=>$en?'Changes needed':'Aanpassen nodig','image_wait'=>$en?'AI image in progress':'AI maakt een afbeelding','published'=>$en?'Published':'Gepubliceerd'][$s->status]??($en?'In progress':'In behandeling');
@endphp
<article class="story-keepsake{{ $s->status==='rejected'?' publication-needs-revision':'' }}">
@if($imageUrl)<figure class="story-polaroid"><img src="{{ $imageUrl }}" alt="{{ $en?'AI illustration for your story':'AI-illustratie bij je verhaal' }}" loading="lazy" width="96" height="108"><figcaption>{{ $en?'AI illustration':'AI-illustratie' }}</figcaption></figure>@endif
<div class="story-keepsake-body"><div class="story-keepsake-meta"><span class="story-state story-state--{{ $s->status==='published'?'published':($s->status==='rejected'?'revise':'waiting') }}">{{ $statusLabel }}</span><time datetime="{{ $s->created_at->toDateString() }}">{{ $s->created_at->format('d-m-Y') }}</time></div><h3>{{ $s->title }}</h3><p class="story-excerpt">{{ \Illuminate\Support\Str::limit($s->body,150) }}</p>
<details class="story-full-text"><summary>{{ $en?'Read my submitted story':'Lees mijn ingestuurde verhaal' }}</summary><p class="comments">{{ $s->body }}</p></details>
@if($s->status==='rejected')<div class="publication-revision-feedback"><strong>{{ $en?'Please revise your story':'Pas je verhaal aan voor publicatie' }}</strong><p>{{ $en?'This story has not been published. Use the feedback to edit your text and submit it for review again.':'Dit verhaal is nog niet gepubliceerd. Gebruik de feedback om je tekst aan te passen en stuur het opnieuw in voor controle.' }}</p>@if($s->moderator_note||$s->reason)<p class="publication-revision-reason"><b>Feedback:</b> {{ $s->moderator_note?:($s->reason[$en?'en':'nl']??$s->reason['nl']??'') }}</p>@endif</div>
@elseif($s->moderator_note||$s->reason)<p class="story-review-note">{{ $s->moderator_note?:($s->reason[$en?'en':'nl']??'') }}</p>@endif
@if($s->ai_error)<p class="story-review-note">{{ $en?'A service is temporarily unavailable. This is not a rejection of your story.':'Een dienst is tijdelijk niet beschikbaar. Dat is geen afwijzing van je verhaal.' }}</p>@endif
@if($s->status==='published'&&$s->article_id)<a class="story-view-link" href="/?story={{ $s->article_id }}&amp;lang={{ app()->getLocale() }}">{{ $en?'View story':'Bekijk verhaal' }} <span aria-hidden="true">→</span></a>@endif
@if($s->status==='rejected')<div class="story-revision-actions"><a class="story-view-link" href="/mijn-lichtpuntjes/{{ $s->id }}/aanpassen?lang={{ app()->getLocale() }}">{{ $en?'Revise my story':'Verhaal aanpassen' }}</a>@if(!$s->appealed_at)<form method="post" action="/mijn-lichtpuntjes/{{ $s->id }}/herbeoordeling?lang={{ app()->getLocale() }}">@csrf<button class="story-text-button">{{ $en?'Ask for human review':'Vraag een menselijke blik' }}</button></form>@endif</div>@endif
</div></article>
@empty
@if($needsAttention??false)<div class="story-empty"><h3>{{ $en?'All caught up':'Alles bijgewerkt' }}</h3><p>{{ $en?'No stories need changes.':'Er zijn geen verhalen die je hoeft aan te passen.' }}</p></div>@else
<div class="story-empty"><img src="/images/share-choice-story.png" alt="" width="130" height="105"><h3>{{ $en?'A place for your stories':'Een plek voor jouw verhalen' }}</h3><p>{{ $en?'Your stories will appear here with their status. Start with something that made you smile.':'Hier verschijnen straks jouw verhalen en hun status. Begin met iets dat jou een glimlach gaf.' }}</p></div>@endif
@endforelse
@if($submissions->hasPages())<nav class="story-pagination" aria-label="{{ $en?'Story pages':'Verhaalpagina’s' }}">@if($submissions->previousPageUrl())<a href="{{ $submissions->previousPageUrl() }}#earlier-stories">← {{ $en?'Previous':'Vorige' }}</a>@endif<span>{{ $submissions->currentPage() }} / {{ $submissions->lastPage() }}</span>@if($submissions->hasMorePages())<a href="{{ $submissions->nextPageUrl() }}#earlier-stories">{{ $en?'Next':'Volgende' }} →</a>@endif</nav>@endif
</section>
</div></div></div>
@endsection
