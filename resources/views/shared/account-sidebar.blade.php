@php($accountEn = app()->getLocale() === 'en')
@php($sidebarSection = $accountSection ?? 'overview')
<aside class="account-sidebar" aria-label="{{ $accountEn ? 'My account' : 'Mijn account' }}">
<p class="account-sidebar-title">{{ $accountEn ? 'My account' : 'Mijn account' }}</p>
<nav aria-label="{{ $accountEn ? 'Account sections' : 'Accountonderdelen' }}">
@foreach([
 ['/profiel', 'overview', $accountEn ? 'Overview' : 'Overzicht', request()->is('profiel') && $sidebarSection === 'overview'],
 ['/mijn-lichtpuntjes', null, $accountEn ? 'My stories & feedback' : 'Mijn verhalen & feedback', request()->is('mijn-lichtpuntjes','mijn-lichtpuntjes/*')],
 ['/lichtpuntje/schrijven', null, $accountEn ? 'Write a bright spot' : 'Schrijf een lichtpuntje', request()->is('lichtpuntje/schrijven')],
 ['/profiel', 'badges', $accountEn ? 'Bright points & badges' : 'Lichtpunten & badges', request()->is('profiel') && $sidebarSection === 'badges'],
 ['/profiel', 'comments', $accountEn ? 'My comments' : 'Mijn reacties', request()->is('profiel') && $sidebarSection === 'comments'],
 ['/profiel', 'invite', $accountEn ? 'Invite a friend' : 'Nodig iemand uit', request()->is('profiel') && $sidebarSection === 'invite'],
 ['/profiel', 'points', $accountEn ? 'Points history' : 'Puntenoverzicht', request()->is('profiel') && $sidebarSection === 'points'],
 ['/privacy/verzoeken', null, $accountEn ? 'My data' : 'Mijn gegevens', request()->is('privacy/verzoeken')]
] as [$accountPath, $accountSectionLink, $accountLabel, $accountActive])
<a href="{{ $accountPath }}?{{ http_build_query(array_filter(['lang'=>app()->getLocale(),'section'=>$accountSectionLink])) }}" @if($accountActive) aria-current="page" @endif>{{ $accountLabel }}</a>
@endforeach
</nav>
@if(auth()->user()?->is_admin)
<div class="account-admin-menu"><p class="account-sidebar-title">{{ $accountEn ? 'Administration' : 'Beheer' }}</p>@include('shared.admin-sidebar')</div>
@endif
</aside>
