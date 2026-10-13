@if(\App\Support\Analytics::eligible(request()))
@php
    $consent = \App\Support\CookieChoice::allowed(request());
    $parts = explode('|', (string) request()->cookie('lichtpunt_consent'));
    $expires = $consent ? (((int) $parts[2]) + \App\Support\CookieChoice::MINUTES * 60) * 1000 : 0;
    $analyticsSettings = ['id'=>\App\Support\Analytics::id(),'allowed'=>$consent,'expires'=>$expires,'nonce'=>request()->attributes->get('csp_nonce')];
@endphp
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
window.lichtpuntAnalyticsConfig = @json($analyticsSettings);
</script>
<script nonce="{{ request()->attributes->get('csp_nonce') }}" defer src="/js/analytics.js?v={{ filemtime(public_path('js/analytics.js')) }}"></script>
@endif
