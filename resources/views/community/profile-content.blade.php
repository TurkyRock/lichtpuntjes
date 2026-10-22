@php
$profileEn = app()->getLocale() === 'en';
$sectionTitles = [
 'overview' => $profileEn ? 'Your little happiness history' : 'Jouw kleine geluksgeschiedenis',
 'badges' => $profileEn ? 'Bright points & badges' : 'Lichtpunten & badges',
 'comments' => $profileEn ? 'My comments' : 'Mijn reacties',
 'invite' => $profileEn ? 'Invite a friend' : 'Nodig iemand uit',
 'points' => $profileEn ? 'Points history' : 'Puntenoverzicht',
];
@endphp
<div data-account-section="{{ $accountSection }}">
<h1>{{ $sectionTitles[$accountSection] }}</h1>
@if($accountSection === 'overview')
<p class="account-intro">{{ $profileEn ? 'Good to see you, '.$user->name.'. Here are your little joys and kind contributions.' : 'Fijn dat je er bent, '.$user->name.'. Hier vind je jouw kleine gelukjes en vriendelijke bijdragen.' }}</p>
@include('community.profile-photo')
@if(!$user->hasVerifiedEmail())
<div class="panel sunny"><h2>{{ $profileEn ? 'Confirm your email address' : 'Bevestig je e-mailadres' }}</h2><p>{{ $profileEn ? 'Then you can comment and collect bright points.' : 'Daarna kun je reageren en lichtpunten verzamelen.' }}</p><form method="post" action="{{ route('verification.send') }}">@csrf<button>{{ $profileEn ? 'Send a confirmation link' : 'Stuur een bevestigingslink' }}</button></form></div>
@endif
@if(!$user->community_accepted_at)
<section class="panel">@include($profileEn ? 'community.en.intentions' : 'community.intentions')<form method="post" action="/huisregels/accepteren">@csrf<label><input type="checkbox" name="intentions" value="1" required> {{ $profileEn ? 'I accept the community rules and will contribute kindly.' : 'Ik ga akkoord met de huisregels en breng een vriendelijke bijdrage.' }}</label><p><button>{{ $profileEn ? 'I’ll bring some sunshine ☀' : 'Ik doe positief mee ☀' }}</button></p></form></section>
@endif
<div class="account-overview-stats activity-overview-stats">
<section class="panel paper-stat"><strong>{{ $activityCalendar['visits'] }}</strong><p>{{ $profileEn?'visit days this month':'bezoekdagen deze maand' }}</p></section>
<section class="panel paper-stat"><strong>{{ $activityCalendar['active_days'] }}</strong><p>{{ $profileEn?'days with activity this month':'dagen iets moois gedaan deze maand' }}</p></section>
<section class="panel paper-stat paper-stat-points"><strong>{{ $progress['points'] }}</strong><p>{{ $profileEn?'bright points collected':'lichtpunten verzameld' }}</p><a href="/profiel?lang={{ app()->getLocale() }}&amp;section=badges"><img src="{{ $progress['current']['icon'] }}" width="32" height="32" alt=""> {{ $progress['current']['name'] }}</a></section>
</div>
@include('community.account-moment-preview')
<section class="panel"><h2>{{ $profileEn ? 'Make someone’s day a little brighter' : 'Maak iemands dag een beetje lichter' }}</h2><p>{{ $profileEn ? 'Keep a little joy for yourself or share it with others. Have more to tell? Give your story some room.' : 'Bewaar een klein gelukje voor jezelf of deel het met anderen. Heb je meer te vertellen? Geef jouw verhaal de ruimte.' }}</p><div class="row"><a class="button" href="/lichtpuntje/delen?lang={{ app()->getLocale() }}">{{ $profileEn ? 'Share a bright spot' : 'Deel een lichtpuntje' }}</a><a class="button quiet" href="/mijn-lichtpuntjes?lang={{ app()->getLocale() }}">{{ $profileEn ? 'My stories & feedback' : 'Mijn verhalen & feedback' }}</a></div></section>
@elseif($accountSection === 'badges')
@include('community.badge-ladder')
@include('community.points-explained')
@elseif($accountSection === 'comments')
@include('community.my-comments')
@elseif($accountSection === 'invite')
<section class="panel" aria-labelledby="inviteTitle"><h2 id="inviteTitle">{{ $profileEn ? 'Invite a little sunshine' : 'Nodig een zonnetje uit' }}</h2><p>{{ $profileEn ? 'You earn 5 bright points once your friend verifies their email and posts a first positive comment.' : 'Je krijgt 5 lichtpunten zodra je vriend zijn e-mail bevestigt en een eerste positieve reactie plaatst.' }}</p><label for="inviteLink">{{ $profileEn ? 'Your invitation link' : 'Jouw uitnodigingslink' }}</label><input id="inviteLink" readonly value="{{ url('/aanmelden').'?'.http_build_query(['lang'=>app()->getLocale(),'invite'=>$user->invite_code]) }}"><button id="copyInvite" class="quiet" type="button" style="margin-top:10px">{{ $profileEn ? 'Copy invitation' : 'Kopieer uitnodiging' }}</button><p id="inviteStatus" role="status" class="small"></p></section>
<script nonce="{{ request()->attributes->get('csp_nonce') }}">document.getElementById('copyInvite').addEventListener('click',async()=>{const input=document.getElementById('inviteLink');try{await navigator.clipboard.writeText(input.value);document.getElementById('inviteStatus').textContent=@json($profileEn ? 'Copied. Invite someone who could use a smile.' : 'Gekopieerd. Nodig iemand uit die een glimlach kan gebruiken.');}catch(error){input.focus();input.select();document.getElementById('inviteStatus').textContent=@json($profileEn ? 'Copy the selected link using Ctrl+C or ⌘C.' : 'Kopieer de geselecteerde link met Ctrl+C of ⌘C.');}});</script>
@elseif($accountSection === 'points')
<section class="panel"><h2>{{ $profileEn ? 'Your latest bright points' : 'Je laatste lichtpunten' }}</h2>
<div class="table-scroll"><table class="paper-points-table"><thead><tr><th>{{ $profileEn?'Date':'Datum' }}</th><th>{{ $profileEn?'A little light left behind':'Een beetje licht achtergelaten' }}</th><th>{{ $profileEn?'Points':'Punten' }}</th></tr></thead><tbody>
@forelse($events as $event)<tr><td>{{ $event->period }}</td><td>{{ $profileEn ? strtr($event->label, ['Fijn dat je er weer bent!'=>'Good to see you again!','Een eigen verhaal gepubliceerd'=>'Your own story is published','Een fijn moment om te bewaren'=>'A moment to keep','Je gaf wat liefde met een hartje'=>'You shared a little love','Een vriendelijke reactie'=>'A friendly comment','Een momentje om te bewaren'=>'A moment to keep','Je dagelijkse dosis goed nieuws'=>'Your daily dose of good news','Een lichtpuntje klaargezet om te delen'=>'A bright spot ready to share','Een vriend bracht een eerste glimlach'=>'A friend brought a first smile']) : $event->label }}</td><td><strong>{{ $event->active ? '+'.$event->points : ($profileEn ? 'withdrawn' : 'ingetrokken') }}</strong></td></tr>@empty<tr><td colspan="3">{{ $profileEn ? 'No points yet. The sun will rise.' : 'Nog geen punten. De zon komt vanzelf op.' }}</td></tr>@endforelse
</tbody></table></div></section>
@endif
</div>
