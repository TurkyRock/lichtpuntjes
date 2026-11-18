@php($submission=$submission??null)
<form class="story-write-form" method="post" action="{{ $submission?'/mijn-lichtpuntjes/'.$submission->id.'/aanpassen':'/lichtpuntje/schrijven' }}?lang={{ app()->getLocale() }}">
@csrf
<label for="story-title">{{ $en?'Title of your story':'Titel van je verhaal' }}</label>
<input id="story-title" name="title" required minlength="5" maxlength="140" value="{{ old('title',$submission?->title) }}" placeholder="{{ $en?'A small gesture that stayed with me':'Een klein gebaar dat me bijbleef' }}">
<div class="story-count" data-story-count="title" aria-hidden="true">0 / 140</div>
<label for="story-body">{{ $en?'Your story':'Jouw verhaal' }}</label>
<textarea id="story-body" name="body" required minlength="120" maxlength="6000" rows="6" aria-describedby="story-length story-ai-note" placeholder="{{ $en?'Share something that made you smile.':'Vertel wat jou een glimlach gaf.' }}">{{ old('body',$submission?->body) }}</textarea>

<div class="story-length-row"><small id="story-length">{{ $en?'120–6,000 characters':'120–6.000 tekens' }}</small><span class="story-count" data-story-count="body" aria-hidden="true">0 / 6000</span></div>
<details class="story-agreement story-options" @if($errors->hasAny(['language','story_place','story_region'])) open @endif><summary>{{ $en?'Language and neighbourhood · optional':'Taal en buurt · optioneel' }}</summary>
<div class="story-language"><label for="story-language">{{ $en?'I write in':'Ik schrijf in' }}</label><select id="story-language" name="language"><option value="nl" @selected(old('language',$submission?->language??($en?'en':'nl'))==='nl')>Nederlands</option><option value="en" @selected(old('language',$submission?->language??($en?'en':'nl'))==='en')>English</option></select></div>
<p class="small">{{ $en?'A local story? Add its town and province. These labels are public; no home address.':'Een buurtverhaal? Vul de plaats en provincie in. Deze labels zijn openbaar; geen huisadres.' }}</p>
<label for="story-place">{{ $en?'Town':'Plaats' }}</label><input id="story-place" name="story_place" maxlength="100" value="{{ old('story_place',$submission?->story_place) }}" placeholder="Arnhem">
<label for="story-region">{{ $en?'Province or region':'Provincie of regio' }}</label><input id="story-region" name="story_region" maxlength="100" value="{{ old('story_region',$submission?->story_region) }}" placeholder="Gelderland">
</details>
<p id="story-ai-note" class="story-ai-note"><span aria-hidden="true">✦</span><span>{{ $en?'After approval, AI creates a fitting image and a translation. Your account name appears with your story.':'Na goedkeuring maakt AI een passend beeld en een vertaling. Je accountnaam komt bij je verhaal.' }}</span></p>
<label class="story-consent"><input type="checkbox" name="intentions" value="1" required @checked(old('intentions')==='1')><span>{{ $en?'My own story, shared with permission. I agree to the rules and AI review, translation and illustration for publication.':'Mijn eigen verhaal, gedeeld met toestemming. Ik ga akkoord met de spelregels en AI-controle, vertaling en illustratie voor publicatie.' }}</span></label>
<div class="story-sensitive-choice">
<input type="hidden" name="sensitive_content" value="none">
<label class="story-consent"><input type="checkbox" id="story-sensitive" name="sensitive_content" value="self" aria-controls="story-sensitive-confirm" @checked(old('sensitive_content')==='self')><span>{{ $en?'I share sensitive information about myself, such as health or faith.':'Ik deel iets gevoeligs over mezelf, zoals gezondheid of geloof.' }}</span></label>
<div class="story-sensitive-confirm" id="story-sensitive-confirm">
<label class="story-consent"><input type="checkbox" name="sensitive_consent" value="1" @checked(old('sensitive_consent')==='1')><span>{{ $en?'I explicitly consent to storing this sensitive information, local AI review and translation, and publication after approval.':'Ik geef uitdrukkelijk toestemming voor opslag van deze gevoelige informatie, lokale AI-controle en vertaling, en publicatie na goedkeuring.' }}</span></label>
<p class="small">{{ $en?'Withdraw consent by permanently deleting your story or removing this information. We do not use it for your interest profile. Images use a general description.':'Je trekt toestemming in door je verhaal definitief te verwijderen of deze informatie weg te halen. We gebruiken die niet voor je interesseprofiel. Beelden gebruiken een algemene beschrijving.' }}</p>
</div></div>
<details class="story-agreement"><summary>{{ $en?'Sharing safely & privacy':'Veilig delen en privacy' }}</summary><p class="small">{{ $en?'Share only what you want to make public. Do not share sensitive information about other people. Anyone can read and copy public stories; search engines can find them.':'Deel alleen wat openbaar mag worden. Deel geen gevoelige informatie over anderen. Iedereen kan openbare verhalen lezen en kopiëren; zoekmachines kunnen ze vinden.' }}</p>@include('community.lightspot-guidelines')@include('shared.privacy-note')</details>
<div class="honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
<button class="story-cut-button" type="submit">{{ $submission?($en?'Resubmit my story':'Verstuur mijn aangepaste verhaal'):($en?'Submit my story':'Verstuur mijn verhaal') }} <span aria-hidden="true">↗</span></button>
<p class="story-after-submit">{{ $en?'We publish your story once review and illustration are ready.':'Je verhaal verschijnt zodra de controle en illustratie klaar zijn.' }}</p>
</form>
