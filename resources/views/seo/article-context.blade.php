<aside class="seo-editorial-note" aria-label="{{ $locale==='en'?'About this story':'Over dit verhaal' }}">
@if($seoArticle->origin==='community')
<p>{{ $locale==='en'?'A personal experience shared by a community member.':'Een persoonlijke ervaring, gedeeld door een communitylid.' }} <a href="{{ $locale==='en'?'/en/how-we-work':'/werkwijze' }}">{{ $locale==='en'?'About contributions and moderation':'Over bijdragen en moderatie' }}</a>.</p>
@else
<p>{{ $locale==='en'?'An AI-assisted summary by Jouw Lichtpuntjes, based on the source linked above.':'Een met AI gemaakte samenvatting van Jouw Lichtpuntjes, gebaseerd op de hierboven vermelde bron.' }} <a href="{{ $locale==='en'?'/en/how-we-work':'/werkwijze' }}">{{ $locale==='en'?'Sources, AI and corrections':'Bronnen, AI en correcties' }}</a>.</p>
@endif
<p><a href="/melden/article/{{ $seoArticle->id }}?lang={{ $locale }}">{{ $locale==='en'?'Report an error or concern':'Meld een fout of probleem' }}</a> · <a href="/contact?lang={{ $locale }}">Contact</a></p>
</aside>
