@php
 $accountEn = app()->getLocale() === 'en';
 $sidebarSection = $accountSection ?? 'overview';
 $accountGroups = [
  ['share', $accountEn ? 'Share' : 'Delen', [
   ['/lichtpuntje/delen', null, $accountEn ? 'Share a bright spot' : 'Deel een lichtpuntje', request()->is('lichtpuntje/delen','lichtpuntje/schrijven'), 'pencil'],
   ['/mijn-momentjes', null, $accountEn ? 'My moments' : 'Mijn momentjes', request()->is('mijn-momentjes'), 'sun'],
   ['/mijn-lichtpuntjes', null, $accountEn ? 'My stories' : 'Mijn verhalen', request()->is('mijn-lichtpuntjes','mijn-lichtpuntjes/*'), 'stories'],
  ]],
  ['activity', $accountEn ? 'My activity' : 'Mijn activiteit', [
   ['/profiel', 'badges', $accountEn ? 'Bright points & badges' : 'Lichtpunten & badges', request()->is('profiel') && $sidebarSection === 'badges', 'trophy'],
   ['/profiel', 'comments', $accountEn ? 'My comments' : 'Mijn reacties', request()->is('profiel') && $sidebarSection === 'comments', 'heart'],
   ['/profiel', 'points', $accountEn ? 'Points history' : 'Puntenoverzicht', request()->is('profiel') && $sidebarSection === 'points', 'plus'],
   ['/mijn-bijdragen', null, $accountEn ? 'My donations' : 'Mijn donaties', request()->is('mijn-bijdragen'), 'donations'],
  ]],
  ['account', 'Account', [
   ['/profiel', 'invite', $accountEn ? 'Invite a friend' : 'Nodig iemand uit', request()->is('profiel') && $sidebarSection === 'invite', 'invite'],
   ['/privacy/verzoeken', null, $accountEn ? 'My data' : 'Mijn gegevens', request()->is('privacy/verzoeken','profiel/email/*','beveiliging','beveiliging/*'), 'settings'],
   ['/mijn-momentjes/prullenbak', null, $accountEn ? 'Trash' : 'Prullenbak', request()->is('mijn-momentjes/prullenbak'), 'trash'],
  ]],
 ];
 $accountCurrentLabel = $accountEn ? 'Overview' : 'Overzicht';
 foreach ($accountGroups as $accountGroup) foreach ($accountGroup[2] as $accountItem) if ($accountItem[3]) $accountCurrentLabel = $accountItem[2];
@endphp
<aside class="account-sidebar account-menu-grouped" aria-label="{{ $accountEn ? 'My account' : 'Mijn account' }}">
<div class="account-menu-heading"><span class="account-menu-sun" aria-hidden="true"></span><p class="account-sidebar-title">{{ $accountEn ? 'My account' : 'Mijn account' }}</p></div>
<button class="account-menu-toggle" type="button" aria-expanded="true" aria-controls="accountGroupedNav" hidden><span>{{ $accountCurrentLabel }}</span><span class="account-menu-toggle-label">Menu</span><svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 7 5 5 5-5"/></svg></button>
<nav id="accountGroupedNav" aria-label="{{ $accountEn ? 'Account sections' : 'Accountonderdelen' }}">
<a class="account-overview-tab" href="/profiel?{{ http_build_query(['lang'=>app()->getLocale(),'section'=>'overview']) }}" @if(request()->is('profiel') && $sidebarSection === 'overview') aria-current="page" @endif>@include('shared.account-menu-icon', ['icon'=>'home'])<span>{{ $accountEn ? 'Overview' : 'Overzicht' }}</span></a>
@foreach($accountGroups as [$accountGroupId, $accountGroupTitle, $accountItems])
<section class="account-menu-group" aria-labelledby="account-group-{{ $accountGroupId }}">
<h2 id="account-group-{{ $accountGroupId }}">{{ $accountGroupTitle }}</h2>
@foreach($accountItems as [$accountPath, $accountSectionLink, $accountLabel, $accountActive, $accountIcon])
<a href="{{ $accountPath }}?{{ http_build_query(array_filter(['lang'=>app()->getLocale(),'section'=>$accountSectionLink])) }}" @if($accountActive) aria-current="page" @endif>@include('shared.account-menu-icon', ['icon' => $accountIcon])<span>{{ $accountLabel }}</span></a>
@endforeach
</section>
@endforeach
</nav>
</aside>
