<link rel="stylesheet" href="/css/our-story.css?v={{ filemtime(public_path('css/our-story.css')) }}">
<section class="mission story-scrapbook" id="about" aria-labelledby="ourStoryTitle">
<div class="story-book-title"><div><div class="eyebrow" data-i="missiontag">{{ $locale === 'en' ? 'Why Jouw Lichtpuntje?' : 'Waarom Jouw Lichtpuntje?' }}</div><h2 id="ourStoryTitle" data-i="missiontitle">@if($locale === 'en')A different perspective.<br>The same world.@elseEen andere blik.<br>Dezelfde wereld.@endif</h2></div><span class="story-paper story-paper-tag">{{ $locale === 'en' ? 'Good news worth keeping' : 'Goed nieuws om te bewaren' }}</span></div>
<div class="story-book-grid">
<article class="story-paper story-intro"><span class="story-tape" aria-hidden="true"></span>
@if($locale === 'en')
<h3>For the little joys.</h3><p>There’s already so much bad news to read. That’s why we’re doing things differently here: no negativity, just good news, lovely moments and stories that make you smile.</p><p>We collect positive news stories from all over the world and make room for your own bright spots. A special encounter, a kind gesture or simply something small that makes your day better. Because happiness is often found in the little things!</p>
@else
<h3>Voor de kleine gelukjes.</h3><p>Er valt al zoveel ellende te lezen. Daarom doen we het hier eens anders: geen negativiteit, maar goed nieuws, mooie momenten en verhalen die je laten glimlachen.</p><p>We verzamelen positieve nieuwsverhalen van over de hele wereld én geven ruimte aan jouw eigen lichtpuntjes. Een bijzondere ontmoeting, een lief gebaar of gewoon iets kleins dat je dag mooier maakt. Want geluk zit vaak in de kleine dingen!</p>
@endif
</article>
<div class="story-paper story-quote"><span class="story-tape" aria-hidden="true"></span>
@if($locale === 'en')
<div class="story-quote-label">A little more today</div><div class="story-letter-word" role="img" aria-label="smile"><span aria-hidden="true">s</span><span aria-hidden="true">m</span><span aria-hidden="true">i</span><span aria-hidden="true">l</span><span aria-hidden="true">e</span></div><div class="story-handwritten">A small act of kindness can be a big bright spot.</div>
@else
<div class="story-quote-label">Vandaag een beetje meer</div><div class="story-letter-word" role="img" aria-label="glimlach"><span aria-hidden="true">g</span><span aria-hidden="true">l</span><span aria-hidden="true">i</span><span aria-hidden="true">m</span><span aria-hidden="true">l</span><span aria-hidden="true">a</span><span aria-hidden="true">c</span><span aria-hidden="true">h</span></div><div class="story-handwritten">Een klein gebaar kan een groot lichtpuntje zijn.</div>
@endif
@include('homepage.paper-sun')
</div>
<article class="story-paper story-source"><span class="story-tape" aria-hidden="true"></span>
@if($locale === 'en')
<h3>Passed on with care.</h3><p>News articles stay with their original creators. We write our own summaries and link you to the full story at the source. That way, we keep things affordable while still spreading a generous dose of positivity.</p>
@else
<h3>Met liefde doorverteld.</h3><p>Nieuwsartikelen blijven netjes bij hun oorspronkelijke makers. Wij schrijven onze eigen samenvattingen en verwijzen je via een bronlink naar het volledige verhaal. Zo houden we het betaalbaar én verspreiden we toch een flinke dosis positiviteit.</p>
@endif
</article>
<article class="story-paper story-ai"><span class="story-tape" aria-hidden="true"></span>
@if($locale === 'en')
<h3>A little wink belongs here.</h3><p>Our images are made with AI and are meant to add a cheerful touch to each story. Spot a cat with five legs, a bicycle without handlebars or another unusual AI creation? Just have a little smile about it. That’s what this website is here for, after all! 😉</p>
@else
<h3>Een knipoog hoort erbij.</h3><p>Onze afbeeldingen worden gemaakt met AI en zijn bedoeld als vrolijk sfeerbeeld bij het verhaal. Zie je een keer een kat met vijf poten, een fiets zonder stuur of een andere bijzondere AI-creatie? Ach, glimlach er vooral even om. Daar is deze website tenslotte voor! 😉</p>
@endif
</article>
</div>
</section>
