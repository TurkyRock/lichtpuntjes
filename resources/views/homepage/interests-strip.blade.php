<aside class="home-interest-strip" aria-label="{{ $locale==='en'?'Personalize your news':'Kies nieuws dat bij je past' }}">
<span class="home-interest-mark" aria-hidden="true">✦</span>
<div class="home-interest-copy">
<strong>{{ $locale==='en'?'Good news that fits your interests?':'Goed nieuws dat bij je past?' }}</strong>
<span>{{ $locale==='en'?'Choose your interests for a more personal selection.':'Kies je interesses en ontdek sneller verhalen die jou aanspreken.' }}</span>
</div>
<a href="/aanmelden?lang={{ $locale }}&amp;interesses=1">{{ $locale==='en'?'Choose your interests':'Kies je interesses' }} →</a>
</aside>
