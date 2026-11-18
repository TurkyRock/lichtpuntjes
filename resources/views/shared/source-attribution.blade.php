@if(!empty($attribution))
<p class="source-attribution small">@if(!empty($attribution['author'])){{ $locale==='en'?'Original author: ':'Oorspronkelijke auteur: ' }}{{ $attribution['author'] }}. @endif
{{ $attribution['credit']??'' }}
{{ $locale==='en'?'Summarised and translated by Jouw Lichtpuntjes.':'Samengevat en vertaald door Jouw Lichtpuntjes.' }}
@if(\App\Support\StorySeo::safeUrl($attribution['license_url']??''))<a href="{{ $attribution['license_url'] }}" target="_blank" rel="noopener noreferrer">{{ $attribution['license'] }}</a>@endif</p>
@endif
