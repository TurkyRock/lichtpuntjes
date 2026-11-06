@php($accountEn = app()->getLocale() === 'en')
@php($sidebarSection = $accountSection ?? 'overview')
<aside class="account-sidebar" aria-label="{{ $accountEn ? 'My account' : 'Mijn account' }}">
<p class="account-sidebar-title">{{ $accountEn ? 'My account' : 'Mijn account' }}</p>
<nav aria-label="{{ $accountEn ? 'Account sections' : 'Accountonderdelen' }}">

@foreach([
 ['/profiel', 'overview', $accountEn ? 'Overview' : 'Overzicht', request()->is('profiel') && $sidebarSection === 'overview'],
 ['/lichtpuntje/delen', null, $accountEn ? 'Share a bright spot' : 'Deel een lichtpuntje', request()->is('lichtpuntje/delen','lichtpuntje/schrijven')],
 ['/mijn-momentjes', null, $accountEn ? 'My moments' : 'Mijn momentjes', request()->is('mijn-momentjes')],
 ['/mijn-lichtpuntjes', null, $accountEn ? 'My stories' : 'Mijn verhalen', request()->is('mijn-lichtpuntjes','mijn-lichtpuntjes/*')],
 ['/profiel', 'badges', $accountEn ? 'Bright points & badges' : 'Lichtpunten & badges', request()->is('profiel') && $sidebarSection === 'badges'],
 ['/profiel', 'comments', $accountEn ? 'My comments' : 'Mijn reacties', request()->is('profiel') && $sidebarSection === 'comments'],
 ['/profiel', 'invite', $accountEn ? 'Invite a friend' : 'Nodig iemand uit', request()->is('profiel') && $sidebarSection === 'invite'],
 ['/profiel', 'points', $accountEn ? 'Points history' : 'Puntenoverzicht', request()->is('profiel') && $sidebarSection === 'points'],
 ['/mijn-bijdragen', null, $accountEn ? 'My contributions' : 'Mijn bijdragen', request()->is('mijn-bijdragen')],
 ['/privacy/verzoeken', null, $accountEn ? 'My data' : 'Mijn gegevens', request()->is('privacy/verzoeken')]
] as [$accountPath, $accountSectionLink, $accountLabel, $accountActive])
<a href="{{ $accountPath }}?{{ http_build_query(array_filter(['lang'=>app()->getLocale(),'section'=>$accountSectionLink])) }}" @if($accountActive) aria-current="page" @endif><span class="account-nav-mark" aria-hidden="true">{{ ['overview'=>'⌂','badges'=>'✦','comments'=>'♡','invite'=>'↗','points'=>'＋'][$accountSectionLink]??($accountPath==='/privacy/verzoeken'?'⚙':($accountPath==='/lichtpuntje/delen'?'✎':($accountPath==='/mijn-momentjes'?'☀':'▤'))) }}</span>{{ $accountLabel }}</a>
@endforeach
</nav>
</aside>
