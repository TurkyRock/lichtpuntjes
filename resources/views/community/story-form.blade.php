@php($submission=$submission??null)
<form class="story-write-form" method="post" action="{{ $submission?'/mijn-lichtpuntjes/'.$submission->id.'/aanpassen':'/lichtpuntje/schrijven' }}?lang={{ app()->getLocale() }}">
@csrf
<label for="story-title">{{ $en?'Title of your story':'Titel van je verhaal' }}</label>
<input id="story-title" name="title" required minlength="5" maxlength="140" value="{{ old('title',$submission?->title) }}" placeholder="{{ $en?'A small gesture that stayed with me':'Een klein gebaar dat me bijbleef' }}">
<div class="story-count" data-story-count="title" aria-hidden="true">0 / 140</div>
<label for="story-body">{{ $en?'Your story':'Jouw verhaal' }}</label>
<textarea id="story-body" name="body" required minlength="120" maxlength="6000" rows="9" aria-describedby="story-length story-ai-note" placeholder="{{ $en?'What happened? What made it special? What smile would you like to pass on?':'Wat gebeurde er? Wat maakte het bijzonder? Welke glimlach wil je doorgeven?' }}">{{ old('body',$submission?->body) }}</textarea>
@include('community.contribution-consent')
<div class="story-length-row"><small id="story-length">{{ $en?'120–6,000 characters':'120–6.000 tekens' }}</small><span class="story-count" data-story-count="body" aria-hidden="true">0 / 6000</span></div>
<div class="story-language"><label for="story-language">{{ $en?'I write in':'Ik schrijf in' }}</label><select id="story-language" name="language"><option value="nl" @selected(old('language',$submission?->language??($en?'en':'nl'))==='nl')>Nederlands</option><option value="en" @selected(old('language',$submission?->language??($en?'en':'nl'))==='en')>English</option></select></div>
<details class="story-agreement"><summary>{{ $en?'Is this a local story? · optional':'Speelt dit in de buurt? · optioneel' }}</summary>
<p class="small">{{ $en?'Add the town and region the story is about, so nearby readers can discover it. These labels are public. Do not enter a home address.':'Vul de plaats en regio in waar je verhaal over gaat, zodat lezers uit de buurt het kunnen ontdekken. Deze labels zijn openbaar. Vul geen huisadres in.' }}</p>
<label for="story-place">{{ $en?'Town in the story':'Plaats van het verhaal' }}</label><input id="story-place" name="story_place" maxlength="100" value="{{ old('story_place',$submission?->story_place) }}" placeholder="Arnhem">
<label for="story-region">{{ $en?'Region or province':'Regio of provincie' }}</label><input id="story-region" name="story_region" maxlength="100" value="{{ old('story_region',$submission?->story_region) }}" placeholder="Gelderland">
</details>
<p id="story-ai-note" class="story-ai-note"><span aria-hidden="true">✦</span><span>{{ $en?'After approval, AI creates a fitting image for your story. No photo upload needed. Your story also gets a Dutch and English version.':'Na goedkeuring maakt AI een passende afbeelding bij jouw verhaal. Je hoeft zelf geen foto te uploaden. Je verhaal krijgt ook een Nederlandse en Engelse versie.' }}</span></p>
<label class="story-consent"><input type="checkbox" name="intentions" value="1" required @checked(old('intentions')==='1')><span>{{ $en?'This is my own story. I have permission where needed, share no sensitive details about others and accept the rules, AI review, illustration and translation for publication.':'Dit is mijn eigen verhaal. Ik heb waar nodig toestemming, deel geen gevoelige gegevens over anderen en ga akkoord met de spelregels, AI-keuring, illustratie en vertaling voor publicatie.' }}</span></label>
<details class="story-agreement"><summary>{{ $en?'Our sharing agreement and privacy':'Onze spelregels en privacy' }}</summary>@include('community.lightspot-guidelines')@include('shared.privacy-note')</details>
<div class="honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
<button class="story-cut-button" type="submit">{{ $submission?($en?'Resubmit my story':'Verstuur mijn aangepaste verhaal'):($en?'Submit my story':'Verstuur mijn verhaal') }} <span aria-hidden="true">↗</span></button>
<p class="story-after-submit">{{ $en?'Your story appears once review and its AI illustration are ready. Your account name is shown as the author.':'Je verhaal verschijnt zodra de controle en AI-illustratie klaar zijn. Je accountnaam staat erbij als auteur.' }}</p>
</form>
