@php($en=app()->getLocale()==='en')
@php($preferences=isset($user)?($user->news_preferences??[]):(auth()->user()?->news_preferences??[]))
<fieldset class="privacy-choice" @if(!empty($registrationForm)) aria-labelledby="registrationPreferencesTitle" @endif>
@if(!empty($registrationForm))
<h3 id="registrationPreferencesTitle">{{ $en?'Your news, your choice (optional)':'Jouw nieuws, jouw keuze (vrijwillig)' }}</h3>
@else
<legend>{{ $en?'Your news, your choice (optional)':'Jouw nieuws, jouw keuze (vrijwillig)' }}</legend>
@endif
<p class="small">{{ $en?'Read everything without a profile, or choose topics yourself. We do not infer health or religion from your contributions. Turning personalisation off deletes these preferences.':'Lees zonder persoonlijk profiel, of kies zelf onderwerpen. We leiden geen gezondheid of geloof af uit je bijdragen. Uitschakelen verwijdert deze voorkeuren.' }}</p>
<label><input type="checkbox" name="personalisation" value="1" @checked(old('personalisation',$preferences['enabled']??false))> {{ $en?'Use my preferences for news recommendations':'Gebruik mijn voorkeuren voor nieuwsaanbevelingen' }}</label>
<label>{{ $en?'Age group (optional)':'Leeftijdscategorie (vrijwillig)' }}<select name="age_group"><option value="">{{ $en?'Prefer not to say':'Liever niet zeggen' }}</option>@foreach(\App\Services\NewsPreferences::AGES as $age)<option value="{{ $age }}" @selected(old('age_group',$preferences['age_group']??'')===$age)>{{ $age==='60plus'?'60+':$age }}</option>@endforeach</select></label>
<label>{{ $en?'Town (optional, no address)':'Woonplaats (vrijwillig, geen adres)' }}<input name="place" maxlength="100" value="{{ old('place',$preferences['place']??'') }}" autocomplete="address-level2"></label>
<div>@foreach(\App\Services\NewsPreferences::TOPICS as $key=>$topic)<label style="display:inline-block;margin:6px"><input type="checkbox" name="interests[]" value="{{ $key }}" @checked(in_array($key,old('interests',$preferences['interests']??[])))> {{ $topic[$en?1:0] }}</label>@endforeach</div>
<label>{{ $en?'News region':'Nieuwsgebied' }}<select name="geography">@foreach(['mixed'=>['Gemengd','Mixed'],'netherlands'=>['Meer Nederlands nieuws','More Dutch news'],'world'=>['Wereldwijd','Worldwide']] as $key=>$label)<option value="{{ $key }}" @selected(old('geography',$preferences['geography']??'mixed')===$key)>{{ $label[$en?1:0] }}</option>@endforeach</select></label>
<p class="small">{{ $en?'Only used when you enable personalisation. You can change this in My details.':'Alleen gebruikt als je personalisatie aanzet. Je kunt dit wijzigen bij Mijn gegevens.' }}</p></fieldset>
