<link rel="stylesheet" href="/css/our-story.css?v={{ filemtime(public_path('css/our-story.css')) }}">
<section class="mission story-scrapbook" id="about" aria-labelledby="ourStoryTitle">
<div class="story-book-title"><div><div class="eyebrow" data-i="missiontag">{{ $locale === 'en' ? 'Why Jouw Lichtpuntje?' : 'Waarom Jouw Lichtpuntje?' }}</div><h2 id="ourStoryTitle" data-i="missiontitle">@if($locale === 'en')A different perspective.<br>The same world.@elseEen andere blik.<br>Dezelfde wereld.@endif</h2></div><span class="story-paper story-paper-tag">{{ $locale === 'en' ? 'Good news worth keeping' : 'Goed nieuws om te bewaren' }}</span></div>
<div class="story-book-grid">
<article class="story-paper story-intro"><span class="story-tape" aria-hidden="true"></span>
@if($locale === 'en')
<h3>For the little joys.</h3><p>I’m Erik, the creator of Lichtpuntjes. I’m building a place where you can take a break with something positive. Thank you for being here!</p><p>There’s already so much bad news to read. That’s why I’m doing things differently here: no negativity, just good news, lovely moments and stories that make you smile.</p><p>I collect positive news stories from all over the world and make room for your own bright spots. A special encounter, a kind gesture or simply something small that makes your day better. Because happiness is often found in the little things!</p>
@else
<h3>Voor de kleine gelukjes.</h3><p>Ik ben Erik, de maker van Lichtpuntjes. Ik bouw aan een plek waar je even kunt opladen met iets positiefs. Fijn dat je er bent!</p><p>Er valt al zoveel ellende te lezen. Daarom doe ik het hier eens anders: geen negativiteit, maar goed nieuws, mooie momenten en verhalen die je laten glimlachen.</p><p>Ik verzamel positieve nieuwsverhalen van over de hele wereld én geef ruimte aan jouw eigen lichtpuntjes. Een bijzondere ontmoeting, een lief gebaar of gewoon iets kleins dat je dag mooier maakt. Want geluk zit vaak in de kleine dingen!</p>
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
<h3>Passed on with care.</h3><p>News articles stay with their original creators. With AI assistance, I create summaries and link you to the full story at the source. That way, I keep things affordable while still passing on a generous dose of positivity.</p>
@else
<h3>Met liefde doorverteld.</h3><p>Nieuwsartikelen blijven netjes bij hun oorspronkelijke makers. Met hulp van AI maak ik eigen samenvattingen en verwijs ik je via een bronlink naar het volledige verhaal. Zo houd ik het betaalbaar én geef ik toch een flinke dosis positiviteit door.</p>
@endif
</article>
<article class="story-paper story-ai" id="about-ai" tabindex="-1"><span class="story-tape" aria-hidden="true"></span>
@if($locale === 'en')
<h3>A little wink belongs here.</h3><p>I use AI to create images that add a cheerful touch to each story. They are not photographs of the reported events. Spot a cat with five legs, a bicycle without handlebars or another unusual AI creation? Just have a little smile about it. That’s what this website is here for, after all! 😉</p>
@else
<h3>Een knipoog hoort erbij.</h3><p>Ik maak de afbeeldingen met AI als vrolijk sfeerbeeld bij het verhaal. Het zijn geen foto’s van de beschreven gebeurtenissen. Zie je een keer een kat met vijf poten, een fiets zonder stuur of een andere bijzondere AI-creatie? Ach, glimlach er vooral even om. Daar is deze website tenslotte voor! 😉</p>
@endif
</article>
</div>
</section>
