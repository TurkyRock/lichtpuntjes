@extends('community.layout')
@push('page-styles')
<link rel="stylesheet" href="/css/news-preferences.css?v={{ filemtime(public_path('css/news-preferences.css')) }}">
@endpush
@section('content')
@php
 $en=app()->getLocale()==='en'; $lang=app()->getLocale();
 $chosen=old('interests', $preferences['interests']??[]);
 $less=old('less_topics', $preferences['less_topics']??[]);
@endphp
<div class="news-preferences">
<p class="preferences-eyebrow">{{ $welcome ? ($en?'A little more you · optional':'Een beetje meer jij · vrijblijvend') : ($en?'Your daily light':'Jouw dagelijkse licht') }}</p>
<h1>{{ $en?'What makes you smile?':'Waar krijg jij een glimlach van?' }}</h1>
<p class="account-intro">{{ $en?'A discovery, a kind gesture or good news from nearby? Choose what you enjoy reading.':'Een ontdekking, een mooi gebaar of goed nieuws van dichtbij? Kies wat jij graag leest.' }}</p>
<form method="post" action="/mijn-interesses?lang={{ $lang }}" class="preferences-form">
@csrf @method('PUT')
<input type="hidden" name="welcome" value="{{ $welcome?1:0 }}">
<section class="panel preferences-paper">
<fieldset><legend>{{ $en?'Your interests':'Dit vind ik interessant' }}</legend>
<p class="preferences-hint" id="interestHint">{{ $en?'Choose as many as you like. Leave empty for a broad selection.':'Kies zoveel onderwerpen als je wilt. Laat leeg voor een brede selectie.' }}</p>
<div class="preference-topics" aria-describedby="interestHint">
@foreach(\App\Services\NewsPreferences::TOPICS as $key=>$labels)
<label><input type="checkbox" name="interests[]" value="{{ $key }}" @checked(in_array($key,$chosen))><span>{{ $labels[$en?1:0] }}</span></label>
@endforeach
</div></fieldset>
<fieldset><legend>{{ $en?'Where should your news come from?':'Waar komen jouw lichtpuntjes vandaan?' }}</legend>
<div class="preference-geography">
@foreach(['netherlands'=>[$en?'Mostly the Netherlands':'Vooral Nederland',$en?'More stories about the Netherlands, with room for the world.':'Vaker nieuws over Nederland, met ruimte voor de wereld.'],'mixed'=>[$en?'The Netherlands and the world':'Nederland en de wereld',$en?'A broad mix, with a little extra attention to the Netherlands.':'Een brede mix, met wat extra aandacht voor Nederland.'],'world'=>[$en?'Worldwide':'Wereldwijd',$en?'No country preference.':'Geen voorkeur voor een land.']] as $key=>[$title,$description])
<label><input type="radio" name="geography" value="{{ $key }}" @checked(old('geography',$preferences['geography']??'mixed')===$key) required><span><strong>{{ $title }}</strong><small>{{ $description }}</small></span></label>
@endforeach
</div></fieldset>
<details class="preference-details" @if(old('region',$preferences['region']??'') || old('place',$preferences['place']??'') || old('age_group',$preferences['age_group']??'')) open @endif><summary>{{ $en?'A little more personal · optional':'Nog iets persoonlijker · optioneel' }}</summary>
<div class="preference-personal">
<div><label for="preferencePlace">{{ $en?'Town':'Woonplaats' }}</label><input id="preferencePlace" name="place" maxlength="100" autocomplete="address-level2" value="{{ old('place',$preferences['place']??'') }}" placeholder="{{ $en?'For example Arnhem or Gelderland':'Bijvoorbeeld Arnhem of Gelderland' }}"><small>{{ $en?'One town or region, no street address. Nearby stories depend on what is available.':'Eén plaats of regio, geen adres. Nieuws uit de buurt hangt af van het aanbod.' }}</small></div>
<div><label for="preferenceRegion">{{ $en?'Region or province · optional':'Regio of provincie · optioneel' }}</label><input id="preferenceRegion" name="region" maxlength="100" value="{{ old('region',$preferences['region']??'') }}" placeholder="Gelderland"><small>{{ $en?'Also includes community stories about this area. We match public story labels; recognised municipalities also match their province.':'Ook voor zelfgeschreven verhalen uit deze buurt. We vergelijken openbare plaatslabels; herkende gemeenten koppelen we ook aan hun provincie.' }}</small></div>
<div><label for="preferenceAge">{{ $en?'Age group':'Leeftijdsgroep' }}</label><select id="preferenceAge" name="age_group">
@foreach([''=>($en?'Prefer not to say':'Zeg ik liever niet'),'18-24'=>'18–24','25-39'=>'25–39','40-59'=>'40–59','60plus'=>'60+'] as $key=>$title)
<option value="{{ $key }}" @selected(old('age_group',$preferences['age_group']??'')===$key)>{{ $title }}</option>
@endforeach
</select><small>{{ $en?'A small extra signal for explicitly age-related stories. Your interests come first.':'Een klein extra zetje bij nieuws dat uitdrukkelijk over een levensfase gaat. Je interesses staan voorop.' }}</small></div>
</div></details>
<details class="preference-details" @if($less) open @endif><summary>{{ $en?'Topics you would like to see less':'Onderwerpen die je minder wilt zien' }}</summary>
<p class="preferences-hint">{{ $en?'These move lower in “For you”. They remain available under “All bright spots”.':'Deze komen lager in ‘Voor jou’. Ze blijven beschikbaar bij ‘Alle lichtpuntjes’.' }}</p>
<div class="preference-topics">
@foreach(\App\Services\NewsPreferences::TOPICS as $key=>$labels)
<label><input type="checkbox" name="less_topics[]" value="{{ $key }}" @checked(in_array($key,$less))><span>{{ $labels[$en?1:0] }}</span></label>
@endforeach
</div></details>
<div class="preference-enable"><input type="hidden" name="enabled" value="0"><label><input type="checkbox" name="enabled" value="1" @checked(old('enabled',$preferences['enabled']??false))><span>{{ $en?'Show my personal selection under “For you”':'Toon mijn persoonlijke selectie onder ‘Voor jou’' }}</span></label></div>
<div class="preference-enable"><input type="hidden" name="learn_from_reading" value="0"><label><input type="checkbox" name="learn_from_reading" value="1" @checked(old('learn_from_reading',$preferences['learn_from_reading']??false))><span>{{ $en?'Let “For you” learn from the news I read and like':'Laat ‘Voor jou’ leren van het nieuws dat ik lees en een hartje geef' }}</span></label><p class="preferences-hint">{{ $en?'Optional. Likes weigh more than reads. Your choices come first; recent interests weigh more. We use up to 90 days of activity and keep room for discoveries. Turning this off deletes your learning history.':'Vrijwillig. Hartjes tellen zwaarder dan lezen. Je eigen keuzes blijven leidend; recente interesses wegen meer mee. We gebruiken maximaal 90 dagen aan activiteit en houden ruimte voor verrassing. Uitzetten verwijdert je leergeschiedenis.' }}</p></div>
<div class="preferences-actions"><button type="submit">{{ $welcome?($en?'Find my bright spots →':'Vind mijn lichtpuntjes →'):($en?'Save my preferences':'Bewaar mijn voorkeuren') }}</button><a href="{{ $en?'/en':'/' }}?view=all">{{ $en?'Browse all bright spots':'Bekijk alle lichtpuntjes' }}</a></div>
</section>
</form>
<aside class="preferences-explanation"><strong>{{ $en?'Your news, your choice':'Jouw nieuws, jouw keuze' }}</strong><p>{{ $en?'We use these optional details to order existing stories. Interests weigh most; age never excludes a story. We keep some variety. Your preferences are private and are not sent to AI services or analytics. You can change, disable or remove them here at any time. Disabling deletes your saved preferences and learning history. Learning uses broad news topics, never the contents of your contributions or a health or religious profile. Your private location is never used as a public story label.':'We gebruiken deze optionele gegevens om bestaande artikelen te ordenen. Interesses tellen het zwaarst; leeftijd sluit nooit nieuws uit. We houden ruimte voor verrassing. Je voorkeuren zijn privé en gaan niet naar AI-diensten of bezoekersstatistieken. Je kunt ze hier altijd aanpassen, uitzetten of verwijderen. Uitzetten verwijdert je opgeslagen voorkeuren en leergeschiedenis. Leren gebruikt brede nieuwsonderwerpen, nooit de inhoud van je bijdragen of een gezondheids- of geloofsprofiel. Je eigen woonplaats wordt nooit een openbaar label bij je verhaal.' }}</p><a href="/privacy?lang={{ $lang }}">{{ $en?'Read about your data':'Lees meer over je gegevens' }}</a></aside>
@if($welcome)<form method="post" action="/mijn-interesses/overslaan?lang={{ $lang }}" class="preferences-later">@csrf<button class="quiet">{{ $en?'Skip for now':'Nu overslaan' }}</button></form>@endif
@if($preferences)<details class="preference-remove"><summary>{{ $en?'Remove all my preferences':'Al mijn voorkeuren verwijderen' }}</summary><p>{{ $en?'This also removes your town, age group and “less of this” choices. Your account stays.':'Dit verwijdert ook je woonplaats, leeftijdsgroep en ‘minder van dit’-keuzes. Je account blijft bestaan.' }}</p><form method="post" action="/mijn-interesses?lang={{ $lang }}">@csrf @method('DELETE')<button class="quiet">{{ $en?'Remove my preferences':'Verwijder mijn voorkeuren' }}</button></form></details>@endif
</div>
@endsection
