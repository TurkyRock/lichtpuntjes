<meta name="description" content="{{ $seo['description'] }}"><title>{{ $seo['title'] }}</title>
<link rel="canonical" href="{{ $seo['canonical'] }}">
@foreach($seo['alternates'] as $code=>$href)<link rel="alternate" hreflang="{{ $code }}" href="{{ $href }}">@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $seo['alternates']['nl'] }}">
<meta property="og:type" content="{{ $seo['type'] }}"><meta property="og:site_name" content="Jouw Lichtpuntje"><meta property="og:title" content="{{ $seo['title'] }}"><meta property="og:description" content="{{ $seo['description'] }}"><meta property="og:url" content="{{ $seo['canonical'] }}"><meta property="og:locale" content="{{ $locale==='en'?'en_GB':'nl_NL' }}">
@if($seo['image'])<meta property="og:image" content="{{ $seo['image'] }}"><meta name="twitter:card" content="summary_large_image">@endif
<script type="application/ld+json" nonce="{{ request()->attributes->get('csp_nonce') }}">{!! json_encode($seo['schema'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
