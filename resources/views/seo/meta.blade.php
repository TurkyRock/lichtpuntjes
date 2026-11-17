<meta name="description" content="{{ $seo['description'] }}"><title>{{ $seo['title'] }}</title>
<meta name="robots" content="{{ $seo['robots'] ?? 'max-image-preview:large' }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">
@foreach($seo['alternates'] as $code=>$href)<link rel="alternate" hreflang="{{ $code }}" href="{{ $href }}">@endforeach
<link rel="alternate" hreflang="x-default" href="{{ $seo['alternates']['nl'] }}">
<meta property="og:type" content="{{ $seo['type'] }}"><meta property="og:site_name" content="Jouw Lichtpuntjes"><meta property="og:title" content="{{ $seo['title'] }}"><meta property="og:description" content="{{ $seo['description'] }}"><meta property="og:url" content="{{ $seo['canonical'] }}"><meta property="og:locale" content="{{ $locale==='en'?'en_GB':'nl_NL' }}"><meta property="og:locale:alternate" content="{{ $locale==='en'?'nl_NL':'en_GB' }}">
<meta name="twitter:title" content="{{ $seo['title'] }}"><meta name="twitter:description" content="{{ $seo['description'] }}">
@if($seo['image'])<meta property="og:image" content="{{ $seo['image'] }}"><meta property="og:image:alt" content="{{ $seo['heading'] }}"><meta name="twitter:image" content="{{ $seo['image'] }}"><meta name="twitter:card" content="summary_large_image">@endif
<script type="application/ld+json" nonce="{{ request()->attributes->get('csp_nonce') }}">{!! json_encode($seo['schema'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
