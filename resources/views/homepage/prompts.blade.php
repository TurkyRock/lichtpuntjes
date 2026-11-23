<section class="homepage-daily-prompts" aria-labelledby="homepage-prompts-title">
<h2 id="homepage-prompts-title">{{ $locale==='en'?'What made your day brighter?':'Wat maakte jouw dag lichter?' }}</h2>
<div class="homepage-prompt-grid">
<article class="homepage-prompt homepage-prompt--moment">
<span class="homepage-prompt-tag">{{ $locale==='en'?'A little moment':'Een klein momentje' }}</span>
<h3>{{ $locale==='en'?'Who did something kind for you today?':'Wie deed vandaag iets aardigs voor je?' }}</h3>
<p>{{ $locale==='en'?'A few sentences are enough. Keep your moment to yourself or share it with others.':'Een paar zinnen zijn genoeg. Bewaar je momentje voor jezelf of deel het met anderen.' }}</p>
<div class="homepage-prompt-actions"><a class="homepage-prompt-button" data-account-link href="/lichtpuntje/delen?lang={{ $locale }}">{{ $locale==='en'?'Write a moment':'Schrijf een momentje' }}</a><a href="/momentjes?lang={{ $locale }}">{{ $locale==='en'?'Read Moments':'Lees Momentjes' }}</a></div>
</article>
<article class="homepage-prompt homepage-prompt--story">
<span class="homepage-prompt-tag">{{ $locale==='en'?'Your own story':'Een eigen verhaal' }}</span>
<h3>{{ $locale==='en'?'Which good initiative deserves more attention?':'Welk mooi initiatief verdient meer aandacht?' }}</h3>
<p>{{ $locale==='en'?'Tell us about something in your neighborhood, a special meeting or a hopeful idea.':'Vertel over iets in jouw buurt, een bijzondere ontmoeting of een hoopvol idee.' }}</p>
<div class="homepage-prompt-actions"><a class="homepage-prompt-button homepage-prompt-button--quiet" data-account-link href="/lichtpuntje/schrijven?lang={{ $locale }}">{{ $locale==='en'?'Write a story':'Schrijf een verhaal' }}</a></div>
</article>
</div>
</section>
