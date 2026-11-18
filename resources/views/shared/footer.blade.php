@php($footerEn=app()->getLocale()==='en')
@once
<link rel="stylesheet" href="/css/site-footer.css?v={{ filemtime(public_path('css/site-footer.css')) }}">
<script src="/js/site-footer.js?v={{ filemtime(public_path('js/site-footer.js')) }}" defer nonce="{{ request()->attributes->get('csp_nonce') }}"></script>
@endonce
<footer class="site-footer site-footer-paper" aria-label="{{ $footerEn?'Website information':'Website-informatie' }}">
<div class="footer-paper">
<div class="footer-top"><a class="footer-brand" href="{{ $footerEn?'/en':'/' }}" data-footer-home><img src="/images/site-paper-sun.svg" alt="" width="34" height="34"><span><strong>Jouw Lichtpuntjes<span>.</span></strong><small data-footer-nl="Goed nieuws om door te geven." data-footer-en="Good news to pass on.">{{ $footerEn?'Good news to pass on.':'Goed nieuws om door te geven.' }}</small></span></a><a class="footer-support" href="/steun?lang={{ $footerEn?'en':'nl' }}" data-footer-query><span data-footer-nl="Steun ons" data-footer-en="Support us">{{ $footerEn?'Support us':'Steun ons' }}</span><span aria-hidden="true">♡</span></a></div>
<nav class="footer-links" aria-label="{{ $footerEn?'About the website':'Over de website' }}">
<a href="{{ $footerEn?'/en/about':'/over-ons' }}" data-footer-href-nl="/over-ons" data-footer-href-en="/en/about" data-footer-nl="Over ons" data-footer-en="About us">{{ $footerEn?'About us':'Over ons' }}</a>
<a href="{{ $footerEn?'/en/how-we-work':'/werkwijze' }}" data-footer-href-nl="/werkwijze" data-footer-href-en="/en/how-we-work" data-footer-nl="Bronnen & AI" data-footer-en="Sources & AI">{{ $footerEn?'Sources & AI':'Bronnen & AI' }}</a>
<a href="/contact?lang={{ $footerEn?'en':'nl' }}" data-footer-query>Contact</a>
<a href="/archief?lang={{ $footerEn?'en':'nl' }}" data-footer-query data-footer-nl="Nieuwsarchief" data-footer-en="News archive">{{ $footerEn?'News archive':'Nieuwsarchief' }}</a>
<a href="/steun/beheren?lang={{ $footerEn?'en':'nl' }}" data-footer-query data-footer-nl="Steun beheren" data-footer-en="Manage support">{{ $footerEn?'Manage support':'Steun beheren' }}</a>
</nav>
<div class="footer-bottom"><span class="footer-copyright">© {{ now()->year }} Jouw Lichtpuntjes · <a href="https://newsdata.io" target="_blank" rel="noopener noreferrer">Powered by NewsData.io API</a></span><nav class="footer-legal" aria-label="{{ $footerEn?'Privacy and rules':'Privacy en afspraken' }}"><a href="/privacy?lang={{ $footerEn?'en':'nl' }}" data-footer-query>Privacy</a><a href="/huisregels?lang={{ $footerEn?'en':'nl' }}" data-footer-query data-footer-nl="Huisregels" data-footer-en="Community rules">{{ $footerEn?'Community rules':'Huisregels' }}</a><a href="/cookies?lang={{ $footerEn?'en':'nl' }}" data-footer-query>Cookies</a><button type="button" class="footer-cookie-settings" data-cookie-settings data-footer-nl="Cookie-instellingen" data-footer-en="Cookie settings">{{ $footerEn?'Cookie settings':'Cookie-instellingen' }}</button></nav></div>
</div></footer>
