@php
$c=$activityCalendar;$calendarMonth=$c['month'];$selected=$c['selected'];$en=$profileEn;
$monthLink=fn($month)=>'/profiel?'.http_build_query(['lang'=>app()->getLocale(),'month'=>$month->format('Y-m')]).'#activity-calendar';
$labels=$en?['read'=>'Articles read','heart'=>'Hearts given','moment'=>'Approved moments saved','comment'=>'Approved comments','story'=>'Original stories published','share'=>'Stories ready to share','invite'=>'Friends welcomed']:['read'=>'Artikelen gelezen','heart'=>'Hartjes gegeven','moment'=>'Goedgekeurde momentjes bewaard','comment'=>'Goedgekeurde reacties','story'=>'Eigen verhalen gepubliceerd','share'=>'Berichten klaargezet om te delen','invite'=>'Vrienden verwelkomd'];
@endphp
<div class="activity-calendar-layout" id="activity-calendar">
<section class="panel activity-calendar-sheet" aria-labelledby="activityCalendarTitle">
<h2 id="activityCalendarTitle">{{ $en?'My calendar':'Mijn kalender' }}</h2>
<p class="small">{{ $en?'Small moments, lovely memories.':'Kleine momenten, fijne herinneringen.' }}</p>
<div class="activity-month-nav"><a class="quiet" href="{{ $monthLink($calendarMonth->subMonth()) }}" aria-label="{{ $en?'Previous month':'Vorige maand' }}">‹</a><strong>{{ $calendarMonth->locale($en?'en':'nl')->translatedFormat('F Y') }}</strong><a class="quiet" href="{{ $monthLink($calendarMonth->addMonth()) }}" aria-label="{{ $en?'Next month':'Volgende maand' }}">›</a></div>
<div class="activity-month-grid" aria-label="{{ $en?'Choose a day to view your activity':'Kies een dag om je activiteiten te bekijken' }}">
@foreach($en?['Mo','Tu','We','Th','Fr','Sa','Su']:['ma','di','wo','do','vr','za','zo'] as $weekday)<strong class="activity-weekday">{{ $weekday }}</strong>@endforeach
@for($i=1;$i<$calendarMonth->dayOfWeekIso;$i++)<span class="activity-empty" aria-hidden="true"></span>@endfor
@for($day=1;$day<=$calendarMonth->daysInMonth;$day++)
@php($key=$calendarMonth->day($day)->toDateString())
@php($dayInfo=$c['days'][$key]??[])
<a class="activity-day {{ $selected->toDateString()===$key?'is-selected':'' }} {{ $key===now('Europe/Amsterdam')->toDateString()?'is-today':'' }}" href="/profiel?{{ http_build_query(['lang'=>app()->getLocale(),'month'=>$calendarMonth->format('Y-m'),'day'=>$key]) }}#activity-calendar" @if($selected->toDateString()===$key)aria-current="date"@endif aria-label="{{ $key }}{{ !empty($dayInfo['visited'])?($en?', visited':', langs geweest'):'' }}{{ !empty($dayInfo['activities'])?($en?', activity':', iets gedaan'):'' }}">
<span>{{ $day }}</span><span class="activity-day-marks" aria-hidden="true">@if(!empty($dayInfo['visited']))@include('shared.activity-icon',['kind'=>'visit'])@endif @if(!empty($dayInfo['activities']))<i></i>@endif</span></a>
@endfor
</div>
<div class="activity-legend"><span>@include('shared.activity-icon',['kind'=>'visit']){{ $en?'Visited':'Langs geweest' }}</span><span><i></i>{{ $en?'Did something lovely':'Iets moois gedaan' }}</span></div>
<p class="small activity-history-note">{{ $en?'Visits are recorded from this update onward, only while signed in. Older activity is shown where records are available.':'Bezoekdagen tellen vanaf deze update, alleen als je ingelogd bent. Eerdere activiteiten zie je voor zover ze zijn vastgelegd.' }}</p>
</section>
<section class="panel activity-day-sheet" aria-labelledby="activityDayTitle">
<h2 id="activityDayTitle">{{ $selected->locale($en?'en':'nl')->translatedFormat('l j F') }}</h2>
<p class="activity-earned">{{ (int)($c['detail']['points']??0) }} {{ $en?'bright points earned':'lichtpuntjes erbij' }}</p>
@if(!empty($c['detail']['visited']))<div class="activity-detail-row">@include('shared.activity-icon',['kind'=>'visit'])<div><strong>{{ $en?'Good to see you!':'Fijn dat je er bent!' }}</strong><small>{{ !empty($c['detail']['points_by_kind']['return'])?($en?'Your first visit of the day.':'Je eerste bezoek van de dag.'):($en?'A visit day to keep.':'Een bezoekdag om te bewaren.') }}</small></div>@if(!empty($c['detail']['points_by_kind']['return']))<b>+{{ $c['detail']['points_by_kind']['return'] }}</b>@endif</div>@endif
@foreach($labels as $kind=>$label)
@if(!empty($c['detail']['activities'][$kind])||!empty($c['detail']['points_by_kind'][$kind]))<div class="activity-detail-row">@include('shared.activity-icon',compact('kind'))<div><strong>{{ $c['detail']['activities'][$kind]??'' }} {{ $label }}</strong>@if(empty($c['detail']['activities'][$kind]))<small>{{ $en?'Points awarded after reviewing an earlier contribution.':'Punten na de keuring van een eerdere bijdrage.' }}</small>@endif</div>@if(!empty($c['detail']['points_by_kind'][$kind]))<b>+{{ $c['detail']['points_by_kind'][$kind] }}</b>@endif</div>@endif
@endforeach
@if(empty($c['detail']['visited'])&&empty($c['detail']['activities'])&&empty($c['detail']['points']))<p>{{ $selected->isFuture()?($en?'A fresh page for later.':'Een fris blad voor later.'):($en?'Nothing recorded on this day. A quiet day is fine too.':'Deze dag is er niets vastgelegd. Een rustige dag mag ook.') }}</p>@endif
<p class="small">{{ $en?'Only you can see this overview. Private moments stay private. Counts also include actions that earned no extra points.':'Alleen jij ziet dit overzicht. Privémomentjes blijven privé. Ook acties zonder extra punten tellen mee in de aantallen.' }}</p>
<div class="activity-day-links"><a href="/mijn-momentjes?{{ http_build_query(['lang'=>app()->getLocale(),'month'=>$calendarMonth->format('Y-m'),'day'=>$selected->toDateString()]) }}#moments-list">{{ $en?'Read my moments →':'Lees mijn momentjes →' }}</a><a href="/profiel?lang={{ app()->getLocale() }}&amp;section=points">{{ $en?'Points history →':'Puntenoverzicht →' }}</a></div>
</section>
<p class="activity-gentle-note">{{ $en?'No perfect streak needed. Every visit can be a fresh start.':'Geen perfecte reeks nodig. Ieder bezoek mag een nieuw begin zijn.' }}</p>
</div>
