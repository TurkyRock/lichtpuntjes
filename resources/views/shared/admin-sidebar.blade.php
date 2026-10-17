@php($adminEn=app()->getLocale()==='en')
<aside class="admin-sidebar" aria-label="{{ $adminEn?'Administration':'Beheer' }}">
<nav aria-label="{{ $adminEn?'Administration sections':'Beheeronderdelen' }}">
@foreach([
 ['/beheer', $adminEn?'Comments':'Reacties','comments'],
 ['/beheer/artikelen',$adminEn?'Articles':'Artikelen','articles'],
 ['/beheer/lichtpuntjes',$adminEn?'Community stories':'Eigen lichtpuntjes','stories'],
 ['/beheer/privacy',$adminEn?'Privacy requests':'Privacyverzoeken','privacy']
] as [$adminUrl,$adminLabel,$adminIcon])
@php($adminActive=$adminUrl==='/beheer'?request()->is('beheer'):request()->is(ltrim($adminUrl,'/').'*'))
<a href="{{ $adminUrl }}?lang={{ app()->getLocale() }}" @if($adminActive)aria-current="page"@endif>
<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@if($adminIcon==='comments')<path d="M20 11.5a8 8 0 0 1-8 8H4l2-4A8 8 0 1 1 20 11.5Z"/>
@elseif($adminIcon==='articles')<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>
@elseif($adminIcon==='stories')<path d="M12 20 4.5 12.5C-1 6 7 1 12 7c5-6 13-1 7.5 5.5Z"/>
@else<path d="m12 3 8 4v6c0 4-8 8-8 8s-8-4-8-8V7l8-4Z"/><path d="m8 12 3 3 5-6"/>
@endif
</svg><span>{{ $adminLabel }}</span></a>
@endforeach
</nav></aside>
