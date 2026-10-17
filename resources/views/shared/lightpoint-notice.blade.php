@if(auth()->check()&&auth()->user()->hasVerifiedEmail()&&!auth()->user()->is_suspended)
<link rel="stylesheet" href="/css/lightpoint-notice.css?v={{ filemtime(public_path('css/lightpoint-notice.css')) }}">
<div id="lightpoint-notice" class="lightpoint-notice" popover="manual" data-language="{{ app()->getLocale() }}" data-token="{{ csrf_token() }}">
<div class="lightpoint-glow" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none"><path d="M15 23a9 9 0 1 1 18 0c0 4-5 5-5 10h-8c0-5-5-6-5-10Z" fill="#ffdb68" stroke="#153e39" stroke-width="2"/><path d="M20 37h8m-7 4h6M24 3v5M7 13l4 3m26-3-4 3M4 27h6m28 0h6" stroke="#153e39" stroke-width="2" stroke-linecap="round"/></svg></div>
<div class="lightpoint-notice-copy" role="status" aria-live="polite" aria-atomic="true"><strong data-point-title></strong><span data-point-label></span><a href="/profiel?lang={{ app()->getLocale() }}&amp;section=badges" data-point-badge hidden></a></div>
<button type="button" class="lightpoint-notice-close" aria-label="{{ app()->getLocale()==='en'?'Dismiss notification':'Melding sluiten' }}">×</button>
</div><script src="/js/lightpoint-notice.js?v={{ filemtime(public_path('js/lightpoint-notice.js')) }}" defer></script>
@endif
