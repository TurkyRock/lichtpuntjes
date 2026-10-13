@extends('community.layout')
@section('content')
@php($en=app()->getLocale()==='en')
<h1>{{ $en?'My stories':'Mijn verhalen' }}</h1><p class="account-intro">{{ $en?'Your bright spots, their status and feedback. Nothing is published before approval and an illustration are ready.':'Jouw lichtpuntjes, hun status en feedback. Een verhaal verschijnt pas als de keuring en illustratie klaar zijn.' }}</p>
<p><a class="button" href="/lichtpuntje/schrijven?lang={{ $en?'en':'nl' }}">{{ $en?'Write a bright spot':'Schrijf een lichtpuntje' }}</a></p>
@forelse($submissions as $s)
<section class="panel"><p class="small">{{ $s->created_at->format('d-m-Y H:i') }} · {{ ['pending'=>$en?'Awaiting AI review':'Wacht op AI-keuring','doubt'=>$en?'Human review':'Menselijke beoordeling','rejected'=>$en?'Not approved':'Niet goedgekeurd','image_wait'=>$en?'Approved; illustration being prepared':'Goedgekeurd; illustratie wordt voorbereid','published'=>$en?'Published':'Gepubliceerd'][$s->status]??$s->status }}</p><h2>{{ $s->title }}</h2>
<details><summary>{{ $en?'Read my submitted story':'Lees mijn ingestuurde verhaal' }}</summary><p class="comments">{{ $s->body }}</p></details>
@if($s->moderator_note||$s->reason)<p>{{ $s->moderator_note?:($s->reason[$en?'en':'nl']??'') }}</p>@endif
@if($s->ai_error)<p class="small">{{ $en?'A service is temporarily unavailable. This is not a rejection of your story.':'Een dienst is tijdelijk niet beschikbaar. Dat is geen afwijzing van je verhaal.' }}</p>@endif
@if($s->status==='published'&&$s->article_id)<p><a class="button quiet" href="/?story={{ $s->article_id }}&lang={{ $en?'en':'nl' }}">{{ $en?'View my bright spot':'Bekijk mijn lichtpuntje' }}</a></p>@endif
@if($s->status==='rejected')<div class="row"><a class="button" href="/mijn-lichtpuntjes/{{ $s->id }}/aanpassen?lang={{ $en?'en':'nl' }}">{{ $en?'Revise my story':'Verhaal aanpassen' }}</a>@if(!$s->appealed_at)<form method="post" action="/mijn-lichtpuntjes/{{ $s->id }}/herbeoordeling">@csrf<button class="quiet">{{ $en?'Ask for human review':'Vraag een menselijke blik' }}</button></form>@endif</div>@endif
</section>
@empty<section class="panel"><h2>{{ $en?'Your first story starts here.':'Jouw eerste verhaal begint hier.' }}</h2><p>{{ $en?'Share a small moment that stayed with you. Sometimes a simple kind gesture is the brightest news.':'Deel een klein moment dat je bijbleef. Soms is een eenvoudig lief gebaar het mooiste nieuws.' }}</p></section>@endforelse
<div class="pagination">@if($submissions->previousPageUrl())<a href="{{ $submissions->previousPageUrl() }}">← {{ $en?'Previous':'Vorige' }}</a>@endif @if($submissions->hasMorePages())<a href="{{ $submissions->nextPageUrl() }}">{{ $en?'Next':'Volgende' }} →</a>@endif</div>
@endsection
