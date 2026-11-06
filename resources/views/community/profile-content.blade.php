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
<p class="account-intro">{{ $profileEn ? 'Good to see you, '.$user->name.'. Here are your little joys and kind contributions.' : 'Fijn dat je er bent, '.$user->name.'. Hier vind je jouw kleine gelukjes en vriendelijke bijdragen.' }}</p>
@include('community.profile-photo')
@if(!$user->hasVerifiedEmail())
<div class="panel sunny"><h2>{{ $profileEn ? 'Confirm your email address' : 'Bevestig je e-mailadres' }}</h2><p>{{ $profileEn ? 'Then you can comment and collect bright points.' : 'Daarna kun je reageren en lichtpunten verzamelen.' }}</p><form method="post" action="{{ route('verification.send') }}">@csrf<button>{{ $profileEn ? 'Send a confirmation link' : 'Stuur een bevestigingslink' }}</button></form></div>
@endif
@if(!$user->community_accepted_at)
<section class="panel">@include($profileEn ? 'community.en.intentions' : 'community.intentions')<form method="post" action="/huisregels/accepteren">@csrf<label><input type="checkbox" name="intentions" value="1" required> {{ $profileEn ? 'I accept the community rules and will contribute kindly.' : 'Ik ga akkoord met de huisregels en breng een vriendelijke bijdrage.' }}</label><p><button>{{ $profileEn ? 'I’ll bring some sunshine ☀' : 'Ik doe positief mee ☀' }}</button></p></form></section>
@endif
<div class="account-overview-stats">
<section class="panel paper-stat paper-stat-points"><strong>{{ $progress['points'] }}</strong><p>{{ $profileEn ? 'bright points collected' : 'lichtpunten verzameld' }}</p><a href="/profiel?lang={{ app()->getLocale() }}&amp;section=points">{{ $profileEn ? 'View points history' : 'Bekijk je puntenoverzicht' }}</a></section>
<section class="panel paper-stat paper-stat-badge"><img src="{{ $progress['current']['icon'] }}" width="58" height="58" alt=""><strong>{{ $progress['current']['name'] }}</strong><p>{{ $profileEn ? 'Your current badge' : 'Jouw huidige badge' }}</p><a href="/profiel?lang={{ app()->getLocale() }}&amp;section=badges">{{ $profileEn ? 'View your badges' : 'Bekijk je badges' }}</a></section>
</div>
@include('community.account-moment-preview')
@include('shared.support-note')
<section class="panel"><h2>{{ $profileEn ? 'Make someone’s day a little brighter' : 'Maak iemands dag een beetje lichter' }}</h2><p>{{ $profileEn ? 'Keep a little joy for yourself or share it with others. Have more to tell? Give your story some room.' : 'Bewaar een klein gelukje voor jezelf of deel het met anderen. Heb je meer te vertellen? Geef jouw verhaal de ruimte.' }}</p><div class="row"><a class="button" href="/lichtpuntje/delen?lang={{ app()->getLocale() }}">{{ $profileEn ? 'Share a bright spot' : 'Deel een lichtpuntje' }}</a><a class="button quiet" href="/mijn-lichtpuntjes?lang={{ app()->getLocale() }}">{{ $profileEn ? 'My stories & feedback' : 'Mijn verhalen & feedback' }}</a></div></section>
@elseif($accountSection === 'badges')
@include('community.badge-ladder')
@include('community.points-explained')
@elseif($accountSection === 'comments')
@include('community.my-comments')
@elseif($accountSection === 'invite')
@include('community.invite-studio')
@elseif($accountSection === 'points')
@include('community.points-diary')
@endif
</div>
