@extends('community.layout')
@section('content')
@include('shared.privacy-note')
@php($en=app()->getLocale()==='en')
<h1>{{ $submission?($en?'Revise your bright spot':'Pas je lichtpuntje aan'):($en?'Share your bright spot':'Deel jouw lichtpuntje') }}</h1>
<p class="account-intro">{{ $en?'A story that makes the day a little brighter. In your own words.':'Een verhaal dat de dag een beetje lichter maakt. In jouw eigen woorden.' }}</p>
<section class="panel">@include('community.lightspot-guidelines')</section>
@if(!auth()->user()->hasVerifiedEmail()||!auth()->user()->community_accepted_at)
<section class="panel"><p>{{ $en?'Confirm your email address and accept the community rules in your account before submitting.':'Bevestig je e-mailadres en accepteer de huisregels in je account voordat je een verhaal instuurt.' }}</p><a class="button" href="/profiel?lang={{ $en?'en':'nl' }}">{{ $en?'Go to my account':'Naar mijn account' }}</a></section>
@else
<section class="panel">
@if($submission)<p class="status">{{ $submission->moderator_note?:($submission->reason[$en?'en':'nl']??'') }}</p>@endif
<form method="post" action="{{ $submission?'/mijn-lichtpuntjes/'.$submission->id.'/aanpassen':'/lichtpuntje/schrijven' }}">
@csrf
<label for="title">{{ $en?'A clear title':'Een duidelijke titel' }}</label><input id="title" name="title" required minlength="5" maxlength="140" value="{{ old('title',$submission?->title) }}" placeholder="{{ $en?'The neighbour who made my morning':'De buur die mijn ochtend mooier maakte' }}">
<div class="row"><div style="flex:1;min-width:180px"><label for="language">{{ $en?'I write in':'Ik schrijf in' }}</label><select id="language" name="language"><option value="nl" @selected(old('language',$submission?->language??($en?'en':'nl'))==='nl')>Nederlands</option><option value="en" @selected(old('language',$submission?->language??($en?'en':'nl'))==='en')>English</option></select></div></div>
<label for="body">{{ $en?'Your story':'Jouw verhaal' }}</label><textarea id="body" name="body" required minlength="120" maxlength="6000" rows="12" style="min-height:260px" placeholder="{{ $en?'What happened? What made it special? What smile would you like to pass on?':'Wat gebeurde er? Wat maakte het bijzonder? Welke glimlach wil je doorgeven?' }}">{{ old('body',$submission?->body) }}</textarea>
<p class="small">{{ $en?'120–6,000 characters. Use blank lines between paragraphs. Your account name will be shown publicly as the author. No photo upload is needed: we create an AI illustration after approval.':'120–6.000 tekens. Gebruik witregels tussen alinea’s. Je accountnaam wordt openbaar getoond als auteur. Je hoeft geen foto te uploaden: we maken na goedkeuring een AI-illustratie.' }}</p>
<div class="honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
<label><input type="checkbox" name="intentions" value="1" required> {{ $en?'This is my own story. I have permission where needed, share no private details and accept the rules, AI review, illustration and translation for publication.':'Dit is mijn eigen verhaal. Ik heb waar nodig toestemming, deel geen privégegevens en ga akkoord met de spelregels, AI-keuring, illustratie en vertaling voor publicatie.' }}</label>
<p class="row"><button>{{ $en?'Submit for review':'Verstuur voor controle' }}</button><a href="/mijn-lichtpuntjes?lang={{ $en?'en':'nl' }}">{{ $en?'My stories':'Mijn verhalen' }}</a></p>
</form></section>@endif
@endsection
