@php
$profileEn = app()->getLocale() === 'en';
$sectionTitles = [
 'overview' => $profileEn ? 'My account' : 'Mijn account',
 'badges' => $profileEn ? 'Bright points & badges' : 'Lichtpunten & badges',
 'comments' => $profileEn ? 'My comments' : 'Mijn reacties',
 'invite' => $profileEn ? 'Invite a friend' : 'Nodig iemand uit',
 'points' => $profileEn ? 'Points history' : 'Puntenoverzicht',
];
@endphp
<div data-account-section="{{ $accountSection }}">
<h1>{{ $sectionTitles[$accountSection] }}</h1>
@if($accountSection === 'overview')
<p class="account-intro">{{ $profileEn ? 'Welcome, '.$user->name.'. Choose a section in the menu.' : 'Welkom, '.$user->name.'. Kies een onderdeel in het zijmenu.' }}</p>
@if(!$user->hasVerifiedEmail())
<div class="panel sunny"><h2>{{ $profileEn ? 'Confirm your email address' : 'Bevestig je e-mailadres' }}</h2><p>{{ $profileEn ? 'Then you can comment and collect bright points.' : 'Daarna kun je reageren en lichtpunten verzamelen.' }}</p><form method="post" action="{{ route('verification.send') }}">@csrf<button>{{ $profileEn ? 'Send a confirmation link' : 'Stuur een bevestigingslink' }}</button></form></div>
@endif
@if(!$user->community_accepted_at)
<section class="panel">@include($profileEn ? 'community.en.intentions' : 'community.intentions')<form method="post" action="/huisregels/accepteren">@csrf<label><input type="checkbox" name="intentions" value="1" required> {{ $profileEn ? 'I accept the community rules and will contribute kindly.' : 'Ik ga akkoord met de huisregels en breng een vriendelijke bijdrage.' }}</label><p><button>{{ $profileEn ? 'I’ll bring some sunshine ☀' : 'Ik doe positief mee ☀' }}</button></p></form></section>
@endif
<div class="account-overview-stats">
<section class="panel"><strong>{{ $progress['points'] }}</strong><p>{{ $profileEn ? 'bright points collected' : 'lichtpunten verzameld' }}</p><a href="/profiel?lang={{ app()->getLocale() }}&amp;section=points">{{ $profileEn ? 'View points history' : 'Bekijk je puntenoverzicht' }}</a></section>
<section class="panel"><strong>{{ $progress['current']['name'] }}</strong><p>{{ $profileEn ? 'Your current badge' : 'Jouw huidige badge' }}</p><a href="/profiel?lang={{ app()->getLocale() }}&amp;section=badges">{{ $profileEn ? 'View your badges' : 'Bekijk je badges' }}</a></section>
</div>
<section class="panel"><h2>{{ $profileEn ? 'Your own bright spots' : 'Jouw eigen lichtpuntjes' }}</h2><p>{{ $profileEn ? 'A small victory, a beautiful moment or a kind gesture: share your own story. We review it and create a matching illustration.' : 'Een kleine overwinning, een mooi moment of een lief gebaar: deel jouw eigen verhaal. Wij controleren het en maken een passende illustratie.' }}</p><div class="row"><a class="button" href="/lichtpuntje/schrijven?lang={{ app()->getLocale() }}">{{ $profileEn ? 'Write a bright spot' : 'Schrijf een lichtpuntje' }}</a><a class="button quiet" href="/mijn-lichtpuntjes?lang={{ app()->getLocale() }}">{{ $profileEn ? 'My stories & feedback' : 'Mijn verhalen & feedback' }}</a></div></section>
@elseif($accountSection === 'badges')
@include('community.badge-ladder')
@include('community.points-explained')
@elseif($accountSection === 'comments')
@include('community.my-comments')
@elseif($accountSection === 'invite')
<section class="panel" aria-labelledby="inviteTitle"><h2 id="inviteTitle">{{ $profileEn ? 'Invite a little sunshine' : 'Nodig een zonnetje uit' }}</h2><p>{{ $profileEn ? 'You earn 5 bright points once your friend verifies their email and posts a first positive comment.' : 'Je krijgt 5 lichtpunten zodra je vriend zijn e-mail bevestigt en een eerste positieve reactie plaatst.' }}</p><label for="inviteLink">{{ $profileEn ? 'Your invitation link' : 'Jouw uitnodigingslink' }}</label><input id="inviteLink" readonly value="{{ url('/aanmelden').'?'.http_build_query(['lang'=>app()->getLocale(),'invite'=>$user->invite_code]) }}"><button id="copyInvite" class="quiet" type="button" style="margin-top:10px">{{ $profileEn ? 'Copy invitation' : 'Kopieer uitnodiging' }}</button><p id="inviteStatus" role="status" class="small"></p></section>
<script nonce="{{ request()->attributes->get('csp_nonce') }}">document.getElementById('copyInvite').addEventListener('click',async()=>{const input=document.getElementById('inviteLink');try{await navigator.clipboard.writeText(input.value);document.getElementById('inviteStatus').textContent=@json($profileEn ? 'Copied. Invite someone who could use a smile.' : 'Gekopieerd. Nodig iemand uit die een glimlach kan gebruiken.');}catch(error){input.focus();input.select();document.getElementById('inviteStatus').textContent=@json($profileEn ? 'Copy the selected link using Ctrl+C or ⌘C.' : 'Kopieer de geselecteerde link met Ctrl+C of ⌘C.');}});</script>
@elseif($accountSection === 'points')
<section class="panel"><h2>{{ $profileEn ? 'Your latest bright points' : 'Je laatste lichtpunten' }}</h2>@forelse($events as $event)<p>{{ $event->active ? '+'.$event->points : ($profileEn ? 'withdrawn' : 'ingetrokken') }} · {{ $profileEn ? strtr($event->label, ['Een vriendelijke reactie'=>'A friendly comment','Je dagelijkse dosis goed nieuws'=>'Your daily dose of good news','Een lichtpuntje klaargezet om te delen'=>'A bright spot ready to share','Een vriend bracht een eerste glimlach'=>'A friend brought a first smile']) : $event->label }} <span class="small">{{ $event->period }}</span></p>@empty<p>{{ $profileEn ? 'No points yet. The sun will rise.' : 'Nog geen punten. De zon komt vanzelf op.' }}</p>@endforelse</section>
@endif
</div>
