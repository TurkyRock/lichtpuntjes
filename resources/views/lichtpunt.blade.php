<!doctype html>
<html lang="{{ $locale }}"><head><link rel="icon" type="image/svg+xml" href="/images/site-paper-sun.svg?v=brand20261114">
@include('shared.analytics')
<meta charset="utf-8"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="viewport" content="width=device-width,initial-scale=1">@include('seo.meta')
<link rel="stylesheet" href="/css/home-base.css?v={{ filemtime(public_path('css/home-base.css')) }}">
<link rel="stylesheet" href="/css/site-header.css?v={{ filemtime(public_path('css/site-header.css')) }}">
<link rel="stylesheet" href="/css/home-daily.css?v={{ filemtime(public_path('css/home-daily.css')) }}"><link rel="stylesheet" href="/css/seo.css?v={{ filemtime(public_path('css/seo.css')) }}"><link rel="stylesheet" href="/css/profile-photos.css?v={{ filemtime(public_path('css/profile-photos.css')) }}">@include('homepage.welcome-style')<link rel="stylesheet" href="/css/support.css?v={{ filemtime(public_path('css/support.css')) }}"><link rel="stylesheet" href="/css/article-newspaper.css?v={{ filemtime(public_path('css/article-newspaper.css')) }}"><link rel="stylesheet" href="/css/share-cutouts.css?v={{ filemtime(public_path('css/share-cutouts.css')) }}">
<link rel="stylesheet" href="/css/home-filters.css?v={{ filemtime(public_path('css/home-filters.css')) }}">
<link rel="stylesheet" href="/css/home-highlight-paper.css?v={{ filemtime(public_path('css/home-highlight-paper.css')) }}">
<link rel="stylesheet" href="/css/paper-theme.css?v={{ filemtime(public_path('css/paper-theme.css')) }}"><link rel="stylesheet" href="/css/home-green-board.css?v={{ filemtime(public_path('css/home-green-board.css')) }}">
<link rel="stylesheet" href="/css/site-frame.css?v={{ filemtime(public_path('css/site-frame.css')) }}"><link rel="stylesheet" href="/css/home-mobile.css?v={{ filemtime(public_path('css/home-mobile.css')) }}"><link rel="stylesheet" href="/css/privacy-controls.css?v={{ filemtime(public_path('css/privacy-controls.css')) }}"><link rel="stylesheet" href="/css/seo-updates.css?v={{ filemtime(public_path('css/seo-updates.css')) }}"></head><body class="{{ isset($seoArticle)?'seo-detail':'home-unified' }}">
@include('shared.header', ['home' => !isset($seoArticle)])

<main class="shell">@isset($seoArticle)<section class="article-page"><div class="article-content" id="article">@include('seo.article')</div>@include('seo.article-context')</section>@endisset @if(!isset($seoArticle))<div class="home-paper-board">@endif<section class="daily-welcome hero paper-welcome" data-welcome-version="compact-20261017"><div class="welcome-paper">
<{{ isset($seoArticle)?'p':'h1' }} id="headline">
@if($category!=='all'&&!isset($seoArticle))
{{ \App\Support\StorySeo::heading($locale,$category) }}
@elseif($locale==='en')
Make life a little <span class="lp-title-word">l<span class="lp-title-i">i<span class="lp-title-rays" aria-hidden="true"></span></span>ghter</span>
@else
Maak het leven een stukje <span class="lp-title-word">l<span class="lp-title-i">i<span class="lp-title-rays" aria-hidden="true"></span></span>chter</span>
@endif
</{{ isset($seoArticle)?'p':'h1' }}>

<p class="mobile-home-tagline">{{ $locale==='en'?'your daily dose of positive news':'jouw dagelijkse dosis positief nieuws' }}</p>
<p class="welcome-subtitle" data-i="intro">{{ $locale==='en'?'Here you’ll find only good news and positivity.':'Hier vind je alleen maar goed nieuws en positiviteit.' }}</p>
<div class="daily-welcome-actions"><button type="button" class="daily-yellow" id="openSiteShare" aria-haspopup="dialog" aria-controls="siteShareDialog"><img src="/images/share/paper-share.svg" width="18" height="18" alt=""><span data-i="siteShare">{{ $locale==='en'?'Share':'Delen' }}</span></button><button type="button" class="daily-yellow" id="openStart" aria-haspopup="dialog" aria-controls="startDialog"><img src="/images/share/home.svg" width="18" height="18" alt=""><span data-i="startLink">{{ $locale==='en'?'Set as homepage':'Als startpagina' }}</span></button>
<button type="button" class="daily-yellow" id="openFollowUs" aria-haspopup="dialog" aria-controls="followUsDialog"><span aria-hidden="true">♡</span><span data-nav-nl="Volg ons" data-nav-en="Follow us">{{ $locale==='en'?'Follow us':'Volg ons' }}</span></button>
<a class="daily-account" href="/lichtpuntje/delen?lang={{ $locale }}" data-account-link><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 3 5 5-12 12-6 1 1-6Z"/><path d="m14 5 5 5"/></svg><span class="mobile-share-label">{{ $locale==='en'?'Share something lovely':'Deel iets moois' }}</span><span data-i="shareLightspot">{{ $locale==='en'?'Share a bright spot':'Deel een lichtpuntje' }}</span></a>
</div></div></section>
@include('homepage.highlights')
<section id="stories">@include('homepage.category-line')@include('seo.category-intro')<p id="actionNotice" class="action-notice" role="status" aria-live="polite"></p><div class="grid" id="grid">@include('seo.cards')</div><p class="count" id="count" aria-live="polite"></p><nav class="pagination" id="pagination" aria-label="{{ $locale==='en'?'News pages':'Nieuwspagina’s' }}" @if($feed['pagination']['last_page']<=1) hidden @endif>@if($feed['pagination']['current_page']>1)<a href="{{ \App\Support\StorySeo::home($locale,$category,$feed['pagination']['current_page']-1) }}">{{ $locale==='en'?'Previous':'Vorige' }}</a>@endif @if($feed['pagination']['current_page']<$feed['pagination']['last_page'])<a href="{{ \App\Support\StorySeo::home($locale,$category,$feed['pagination']['current_page']+1) }}">{{ $locale==='en'?'Next':'Volgende' }}</a>@endif</nav></section>
@include('shared.support-note')@include('homepage.our-story')@if(!isset($seoArticle))<div class="mobile-home-ending"><div class="mobile-bottom-cutout" aria-hidden="true"></div><nav class="mobile-bottom-links" aria-label="{{ $locale==='en'?'Website information':'Website-informatie' }}"><span>© Jouw Lichtpuntjes</span><button type="button" class="mobile-about-toggle" aria-controls="about" aria-expanded="false">{{ $locale==='en'?'About us':'Over ons' }}</button><a href="/contact?lang={{ $locale }}">Contact</a><a href="/privacy?lang={{ $locale }}">Privacy</a><button type="button" data-cookie-settings>{{ $locale==='en'?'Cookies':'Cookies' }}</button><a href="/steun?lang={{ $locale }}">{{ $locale==='en'?'Support us':'Steun ons' }}</a><a href="/archief?lang={{ $locale }}">{{ $locale==='en'?'Archive':'Archief' }}</a></nav></div></div>@endif
</main><template id="momentHomeTemplate">@include('homepage.moments')</template>
@include('shared.footer')
<dialog id="momentDetail" class="moment-detail-dialog" aria-labelledby="momentDetailTitle"><div class="moment-detail-heading"><h2 id="momentDetailTitle">{{ $locale==='en'?'A little joy to share':'Een klein gelukje om te delen' }}</h2><button type="button" class="close" data-close-moment aria-label="{{ $locale==='en'?'Close moment':'Momentje sluiten' }}">×</button></div><div id="momentDetailBody"></div></dialog>
<dialog id="reader" class="newspaper-reader" aria-labelledby="articleTitle"><div class="dialog-head"><span class="eyebrow">Jouw Lichtpuntjes · <span id="readerCat"></span></span><button class="close" id="close" aria-label="Sluiten">×</button></div><div class="article-content" id="{{ isset($seoArticle)?'articleModalUnused':'article' }}"></div></dialog>
<dialog id="startDialog" aria-labelledby="startTitle"><div class="dialog-head"><span class="eyebrow">Jouw Lichtpuntjes ☀</span><button class="close" id="closeStart" aria-label="Sluiten">×</button></div><div class="start-content" id="startContent"></div></dialog>
@include('shared.follow-us')
<dialog id="siteShareDialog" aria-labelledby="siteShareTitle"><div class="dialog-head"><span class="eyebrow">Jouw Lichtpuntjes</span><button type="button" class="close" id="closeSiteShare" aria-label="Sluiten">×</button></div><div class="site-share-content"><h2 id="siteShareTitle">Deel een beetje positiviteit</h2><div id="siteShareBody"></div></div></dialog>

<script nonce="{{ request()->attributes->get('csp_nonce') }}">
const signedIn=@json(auth()->check());
let newsView=@json(request('view')==='all'?'all':(($feed['personalized']??false)?'for-you':'all'));
const threadState={},commentDrafts={},replyTargets={};let commentSending=false,shareSending=false,deepLinkOpened=false,shareMenuId=null;

const ui={nl:{badgeSmile:'Glimlachstarter',badgeSmilePoints:'5 lichtpuntjes',badgeSun:'Zonnestraal',badgeSunPoints:'15 lichtpuntjes',badgeLegend:'Lichtpuntlegende',badgeLegendPoints:'150 lichtpuntjes',earnTitle:'Verdien lichtpuntjes!',earnBody:'Lees goed nieuws, deel een verhaal en laat een vriendelijke reactie achter. Zo verzamel je lichtpuntjes en speel je nieuwe badges vrij. Met een gratis account zie je je voortgang en je reacties.',earnJoin:'Maak gratis een account',earnProfile:'Bekijk mijn lichtpuntjes',filterTitle:'Waar word jij blij van?',siteShare:'Delen',topicLabel:'Onderwerp',sortLabel:'Sorteer op',login:'Inloggen',join:'Registreren',about:'Mijn verhaal',headline:"Don't worry,<br><em>Be Happy!</em>",intro:'Jouw dagelijkse portie positiviteit.<br>Start je dag of neem een pauze met positief nieuws, even weg van alle ellende in de wereld.',lightLine:'Maak het leven licht 😉',startLink:'Als startpagina',stories:'Jouw dagelijkse dosis positiviteit',sub:'Grote ontdekkingen. Kleine lichtpuntjes. Ruimte voor hoop.',missiontag:'Waarom Jouw Lichtpuntjes?',missiontitle:'Een andere blik.<br>Dezelfde wereld.',footer:'Goed nieuws, helder verteld. • Nederlands & English',prototype:'Wereldwijd nieuws, zorgvuldig geselecteerd',search:'Zoek een lichtpuntje…',read:'Lees de samenvatting →',source:'Lees verder bij de bron ↗',people:'Mens & maatschappij',sport:'Sport',media:'Media',community:'Zelfgeschreven',all:'Alle lichtpuntjes',nature:'Natuur',energy:'Duurzaamheid',science:'Wetenschap',archive:'Archief',original:'Het volledige verhaal',external:'Opent in een nieuw tabblad · Bron kan een andere taal gebruiken',empty:'Hier zijn nog geen lichtpuntjes. Bekijk een andere categorie of kom later terug.',close:'Sluiten'},en:{badgeSmile:'Smile starter',badgeSmilePoints:'5 bright points',badgeSun:'Sunbeam',badgeSunPoints:'15 bright points',badgeLegend:'Bright point legend',badgeLegendPoints:'150 bright points',earnTitle:'Earn bright points!',earnBody:'Read good news, share a story and leave a friendly comment. Collect bright points and unlock new badges. With a free account, you can see your progress and your comments.',earnJoin:'Create a free account',earnProfile:'View my bright points',filterTitle:'What makes you smile?',siteShare:'Share',topicLabel:'Topic',sortLabel:'Sort by',login:'Log in',join:'Register',about:'My story',headline:"Don't worry,<br><em>Be Happy!</em>",intro:'Your daily dose of positivity.<br>Start your day or take a break with good news, a little breathing room from all the gloom in the world.',lightLine:'Keep life light 😉',startLink:'Set as homepage',stories:'Your daily dose of positivity',sub:'Big discoveries. Small bright spots. Room for hope.',missiontag:'Why Jouw Lichtpuntjes?',missiontitle:'A different perspective.<br>The same world.',footer:'Good news, clearly told. • Nederlands & English',prototype:'Worldwide news, carefully selected',search:'Find a bright spot…',read:'Read the summary →',source:'Continue at the source ↗',people:'People & community',sport:'Sport',media:'Media',community:'Community stories',all:'All bright spots',nature:'Nature',energy:'Sustainability',science:'Science',archive:'Archive',original:'The full story',external:'Opens in a new tab · Source may use a different language',empty:'There are no bright spots here yet. Browse another category or come back later.',close:'Close'}};
document.querySelector('.mobile-about-toggle')?.addEventListener('click',event=>{const button=event.currentTarget,expanded=button.getAttribute('aria-expanded')!=='true';button.setAttribute('aria-expanded',String(expanded));document.body.classList.toggle('mobile-about-open',expanded);if(expanded)document.getElementById('about')?.scrollIntoView({block:'start'});});
@php($seoCatalog = collect(['nl','en'])->mapWithKeys(fn($language)=>[$language=>collect(array_keys(\App\Support\StorySeo::CATEGORIES))->mapWithKeys(fn($key)=>[$key=>['heading'=>\App\Support\StorySeo::heading($language,$key),'description'=>\App\Support\StorySeo::description($language,$key)]]) ]))
const seoCatalog=@json($seoCatalog);
const initialFeed=@json($feed);const detailRelated=@json($seoRelated ?? []);const detailStory=@json($seoStory ?? null);const isDetail=detailStory!==null;const detailBreadcrumb=isDetail?(document.querySelector('.story-breadcrumb')?.outerHTML||''):'';
@php($initialSort = in_array(request('sort'), ['reads','likes'], true) ? request('sort') : 'newest')
let sort=@json($initialSort),openedStory=detailStory,rankDirty=false,actionMessage='';const pendingLikes=new Set();const mobileHomeMedia=matchMedia('(max-width:1023px)');let mobileHomeLimit=3;const compactHome=()=>!isDetail&&mobileHomeMedia.matches;mobileHomeMedia.addEventListener('change',()=>render());let data=initialFeed.articles;let feedState="ready";let currentPage=initialFeed.pagination.current_page;let pagination=initialFeed.pagination;let feedController=null;let feedVersion=0;let feedMinHeight=null;
let lang=@json($locale),category=@json($category),opened=detailStory?detailStory.id:null;const $=id=>document.getElementById(id);const esc=s=>s.replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

function homePath(cat='all',page=1,language=lang){let path=language==='en'?'/en':'/';if(cat!=='all')path=language==='en'?'/en/topics/'+cat:'/nl/onderwerp/'+cat;const params=new URLSearchParams();if(page>1)params.set('page',String(page));if(sort!=='newest')params.set('sort',sort);if(signedIn)params.set('view',newsView);return path+(params.size?'?'+params:'')}
function storyPath(d){return d&&d.story_urls&&d.story_urls[lang]?d.story_urls[lang]:((lang==='en'?'/en/bright-spots/':'/nl/lichtpuntjes/')+encodeURIComponent(d?d.id:''));}
function art(kind){let content=kind==='nature'?'<rect width="400" height="230" fill="#c3e8d9"/><circle cx="290" cy="62" r="33" fill="#ffda68"/><path d="M0 174Q100 62 210 155T400 136V230H0" fill="#6bb79b"/><path d="M0 206Q100 123 240 190T400 168V230H0" fill="#2a7b62"/><path d="M85 228V70M85 152Q25 149 25 86Q83 91 85 134M85 111Q131 112 147 51Q89 55 85 94" stroke="#185b49" fill="#185b49" stroke-width="5"/>':kind==='energy'?'<rect width="400" height="230" fill="#f7e6a9"/><circle cx="82" cy="70" r="38" fill="#ffce49"/><path d="M0 180Q150 127 400 177V230H0" fill="#9bc6a8"/><path d="M261 184V81M261 81L255 30M261 81L304 106M261 81L219 106" stroke="#fefdf2" stroke-width="8"/><path d="M58 160H190L173 216H40Z" fill="#2b6970"/><path d="M71 160L54 216M109 160L92 216M148 160L131 216M56 179H184M49 198H177" stroke="#a1cfce" stroke-width="2"/>':'<rect width="400" height="230" fill="#253f58"/><ellipse cx="223" cy="135" rx="170" ry="60" fill="#74657d" transform="rotate(-23 223 135)"/><ellipse cx="223" cy="135" rx="125" ry="24" fill="#d4a884" transform="rotate(-23 223 135)"/><circle cx="223" cy="135" r="25" fill="#ffe1a3"/><g fill="#fcf7e0"><circle cx="54" cy="49" r="2"/><circle cx="90" cy="93" r="3"/><circle cx="344" cy="48" r="2"/><circle cx="311" cy="195" r="3"/><circle cx="173" cy="42" r="2"/><circle cx="57" cy="191" r="2"/></g>';return '<svg viewBox="0 0 400 230" preserveAspectRatio="xMidYMid slice" aria-hidden="true">'+content+'</svg>'}
function safeWebUrl(url){try{const u=new URL(url);return ['https:','http:'].includes(u.protocol)?esc(u.href):''}catch(e){return ''}}
function usablePhoto(d){return d.photo && typeof d.photo.url==='string' && /^\/news-images\/[a-f0-9]{64}\.(jpg|png)$/.test(d.photo.url)}
function photoCredit(d){if(!usablePhoto(d))return '';const p=d.photo;if(p.generated&&p.style==='lichtpunt-paper-v1')return '';if(p.generated&&p.style==='lichtpunt-photo-v1')return '<div class="photo-credit">'+(lang==='nl'?'AI-gegenereerd sfeerbeeld · Geen foto van de beschreven gebeurtenis.':'AI-generated atmosphere image · Not a photo of the reported event.')+'</div>';if(p.generated)return '<div class="photo-credit">'+(lang==='nl'?'AI-gegenereerde illustratie · Geen foto van de beschreven gebeurtenis.':'AI-generated illustration · Not a photo of the reported event.')+'</div>';const source=safeWebUrl(p.source_url),license=safeWebUrl(p.license_url);return '<div class="photo-credit">'+(p.original?(lang==='nl'?'Foto bij het oorspronkelijke bericht':'Photo from the original story'):(lang==='nl'?'Illustratieve foto':'Illustrative photo'))+' · '+esc(p.creator||'Fotograaf')+' · <a href="'+license+'" target="_blank" rel="noopener noreferrer">'+esc(p.license||'Licentie')+'</a> · <a href="'+source+'" target="_blank" rel="noopener noreferrer">'+esc(p.source_name||'Wikimedia Commons')+' ↗</a><details><summary>'+(lang==='nl'?'Fotogegevens':'Photo details')+'</summary><span>'+esc(p.description||'')+' '+esc(p.date||'')+'</span><br>'+esc(p.attribution||p.credit||'')+'<br>'+(lang==='nl'?'Kaartweergave uitgesneden; oorspronkelijke foto via bronlink.':'Card display cropped; original photo available via source link.')+'</details></div>'}
function aiImageLabel(d){if(!d.photo?.generated)return '';const about=/(?:\/nl\/lichtpuntjes\/|\/en\/bright-spots\/)/.test(window.location.pathname||'')?(lang==='en'?'/en':'/')+'#about-ai':'#about-ai';return '<a class="ai-image-label" href="'+about+'" aria-label="'+(lang==='nl'?'AI-beeld: lees hoe we AI gebruiken':'AI image: read how we use AI')+'">'+(lang==='nl'?'AI-beeld':'AI image')+' <span aria-hidden="true">ⓘ</span></a>'}
function photoMarkup(d,large){if(!usablePhoto(d))return large?'':'<div class="no-photo">'+(lang==='nl'?'Foto nog niet beschikbaar':'Photo not available yet')+'</div>';const p=d.photo;const caption=p.generated?(p.style==='lichtpunt-photo-v1'?(lang==='nl'?'AI-gegenereerd sfeerbeeld':'AI-generated atmosphere image'):(lang==='nl'?'AI-gegenereerde illustratie':'AI-generated illustration')):(lang==='nl'?'Illustratieve foto':'Illustrative photo');const img='<img src="'+esc(p.url)+'" alt="'+esc(p.generated?caption+': '+d[lang].title:(p.description||d[lang].title))+'" width="1024" height="576" '+(large?'fetchpriority="high"':'loading="lazy"')+' decoding="async" data-article-photo>';return large?'<figure class="article-photo">'+img+(photoCredit(d)?'<figcaption>'+photoCredit(d)+'</figcaption>':'')+'</figure>':img}
function hideBrokenPhoto(e){if(e.target.matches && e.target.matches('[data-article-photo]')){e.target.style.display='none';const holder=e.target.closest('.picture,.highlight-art,.article-photo');if(holder&&!holder.querySelector('.no-photo')){holder.querySelectorAll('img').forEach(img=>img.hidden=true);const message=document.createElement('div');message.className='no-photo';message.textContent=lang==='nl'?'Foto tijdelijk niet beschikbaar':'Photo temporarily unavailable';holder.prepend(message)}}}
document.addEventListener('error',hideBrokenPhoto,true);
function date(d){if(!d)return lang==='nl'?'Datum onbekend':'Date unavailable';return new Intl.DateTimeFormat(lang==='nl'?'nl-NL':'en-GB',{day:'numeric',month:'short',year:'numeric',timeZone:'UTC'}).format(new Date(d+'T12:00:00Z'))}
function render(){document.querySelectorAll('[data-nav-nl]').forEach(el=>el.textContent=el.getAttribute('data-nav-'+lang));
 document.querySelectorAll('[data-badge-name]').forEach(el=>el.textContent=el.getAttribute('data-badge-name-'+lang));document.querySelectorAll('[data-badge-points]').forEach(el=>el.textContent=el.getAttribute('data-badge-points')+(lang==='nl'?' lichtpuntjes':' bright points'));document.querySelectorAll("[data-account-link]").forEach(a=>{a.href=a.getAttribute("href").split("?")[0]+"?lang="+lang});renderStart();renderSortTabs();if($('siteShareDialog').open)renderSiteShare();const t=ui[lang];document.documentElement.lang=lang;document.querySelectorAll('[data-i]').forEach(el=>el.innerHTML=t[el.dataset.i]);$('headline').innerHTML=category==='all'||isDetail?t.headline:esc(seoCatalog[lang][category].heading);document.querySelectorAll('[data-lang]').forEach(b=>b.setAttribute('aria-pressed',b.dataset.lang===lang));$('close').setAttribute('aria-label',t.close);$('filters').setAttribute('aria-label',lang==='nl'?'Categorieën':'Categories');$('filters').innerHTML=(compactHome()?['all','community','nature','energy','people','science','media','sport']:['all','community','media','sport','energy','nature','science','people']).map(c=>categoryLineLink(c,t)).join('');keepSelectedCategoryVisible();renderCards();if(opened)renderArticle()}
function momentHomeMarkup(){return category==='all'&&currentPage===1?($('momentHomeTemplate')?.innerHTML||''):''}
function renderCards(){const t=ui[lang],compact=compactHome(),shown=compact?data.slice(0,mobileHomeLimit):data;document.body.classList.toggle('mobile-home-has-more',compact&&data.length>mobileHomeLimit);renderPagination();if(feedState!=='ready'){const message=feedState==='error'?(lang==='nl'?'Het nieuws kon niet worden geladen. Probeer het later opnieuw.':'News could not be loaded. Please try again later.'):(lang==='nl'?'Lichtpuntjes worden geladen…':'Loading bright spots…');$('grid').innerHTML='<div class="empty" role="status">'+message+(feedState==='error'?'<button class="feed-retry" data-retry>'+(lang==='nl'?'Opnieuw proberen':'Try again')+'</button>':'')+'</div>';$('count').textContent='';return}if(data.length===0){$('grid').innerHTML='<p class="empty">'+(category!=='all'?t.empty:(lang==='nl'?'De eerste lichtpuntjes worden voorbereid. Er zijn nog geen berichten gepubliceerd.':'The first bright spots are being prepared. No stories have been published yet.'))+'</p>';$('count').textContent='';return}$('grid').innerHTML=shown.map((d,index)=>'<article class="card" data-read="'+d.id+'"><div class="picture">'+photoMarkup(d,false)+'<span class="category">'+t[d.cat]+'</span></div>'+(d.photo?.generated?'':photoCredit(d))+'<div class="cardbody"><div class="card-metadata"><p class="meta">'+(d.origin==='community'?(lang==='nl'?'Community · ':'Community · '):'')+esc(d.source)+' · '+date(d.date)+'</p>'+aiImageLabel(d)+'</div><h3><a class="card-title" href="'+esc(storyPath(d))+'" aria-haspopup="dialog" aria-controls="reader">'+esc(d[lang].title)+'</a></h3><a class="mobile-card-read card-title" href="'+esc(storyPath(d))+'" aria-haspopup="dialog" aria-controls="reader">'+(lang==='nl'?'Lees dit lichtpuntje →':'Read this bright spot →')+'</a><p class="teaser">'+esc(d[lang].teaser)+'</p><div class="card-actions">'+engagementMarkup(d)+'</div></div></article>'+(!compact&&(index===2||shown.length<3&&index===shown.length-1)?momentHomeMarkup():'')).join('')+(compact?((data.length>mobileHomeLimit?'<div class="mobile-feed-more"><button type="button" data-mobile-more aria-controls="grid">'+(lang==='nl'?'Meer lichtpuntjes →':'More bright spots →')+'</button></div>':'')+momentHomeMarkup()):'')||'<p class="empty">'+t.empty+'</p>';$('count').textContent=pagination.total?(lang==='nl'?'Berichten '+pagination.from+'–'+pagination.to+' van '+pagination.total:pagination.from+'–'+pagination.to+' of '+pagination.total+' stories'):''}
@include('article.newspaper-script')

function brightTitleWord(en){return '<span class="lp-title-word">l<span class="lp-title-i">i<span class="lp-title-rays" aria-hidden="true"></span></span>'+(en?'ghter':'chter')+'</span>';}
Object.assign(ui.nl,{shareLightspot:'Deel een lichtpuntje',headline:'Maak het leven een stukje '+brightTitleWord(false),intro:'Hier vind je alleen maar goed nieuws en positiviteit.',earnJoin:'Registreren',earnProfile:'Mijn lichtpuntjes'});
Object.assign(ui.en,{shareLightspot:'Share a bright spot',headline:'Make life a little '+brightTitleWord(true),intro:'Here you’ll find only good news and positivity.',earnJoin:'Register',earnProfile:'My bright points'});
document.querySelectorAll('[data-lang]').forEach(b=>b.addEventListener('click',()=>{window.location.assign(homePath(category, currentPage, b.dataset.lang))}));$('filters').addEventListener('click',e=>{
 const link=e.target.closest('a[data-cat]');
 if(!link||isDetail||e.defaultPrevented||e.button!==0||e.ctrlKey||e.metaKey||e.shiftKey||e.altKey||link.target==='_blank')return;
 const next=link.dataset.cat;if(!Object.prototype.hasOwnProperty.call(ui[lang],next))return;
 e.preventDefault();if(next===category)return;
 category=next;currentPage=1;rankDirty=false;updateFeedNavigation();loadFeed();
});$('grid').addEventListener('click',e=>{const more=e.target.closest('[data-mobile-more]');if(more){mobileHomeLimit+=3;renderCards();const added=$('grid').querySelectorAll(':scope > .card')[mobileHomeLimit-3];if(added){const link=added.querySelector('.card-title');link?.focus({preventScroll:true});}return;}if(e.target.closest('.moment-home-grid'))return;const like=e.target.closest('[data-like]');if(like){setLike(like.dataset.like);return}if(e.target.closest('[data-retry]')){loadFeed();return}const titleLink=e.target.closest('a.card-title');if(titleLink){if(e.ctrlKey||e.metaKey||e.shiftKey||e.altKey||e.button>0)return;e.preventDefault()}else if(e.target.closest('a,summary,details'))return;const b=e.target.closest('[data-read]');if(b){opened=b.dataset.read;openedStory=data.find(d=>d.id===opened);renderArticle();$('reader').showModal();recordRead(opened);loadComments(opened)}});$('close').addEventListener('click',()=>$('reader').close());$('reader').addEventListener('close',()=>{opened=null;openedStory=null;rankDirty=false;});$('reader').addEventListener('click',e=>{if(e.target===$('reader')){const r=$('reader').getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)$('reader').close()}});render();
function homeFilterIcon(key){return '<svg class="filter-icon" viewBox="0 0 32 32" aria-hidden="true"><use href="/images/home-filter-icons.svg#'+key+'"/></svg>';}
function categoryLineLink(c,t){
 const selected=category===c;
 const label=compactHome()?(c==='all'?(lang==='nl'?'Alles':'All'):c==='community'?(lang==='nl'?'Zelf geschreven':'Community'):t[c]):t[c];
 return '<a data-cat="'+c+'" href="'+homePath(c)+'"'+(selected?' aria-current="page"':'')+'><span class="category-peg" aria-hidden="true"></span>'+homeFilterIcon(c)+'<span class="category-label">'+esc(label).replace('Zelfgeschreven','Zelf<wbr>geschreven').replace('Duurzaamheid','Duur<wbr>zaamheid').replace('Sustainability','Sustain<wbr>ability')+'</span><small class="category-chosen">✓ '+(lang==='nl'?'Gekozen':'Selected')+'</small></a>';
}
function keepSelectedCategoryVisible(){requestAnimationFrame(()=>{const list=$('filters'),selected=list?.querySelector('[aria-current="page"]');if(list&&selected&&list.scrollWidth>list.clientWidth){const delta=selected.getBoundingClientRect().left-list.getBoundingClientRect().left;if(delta<0||delta+selected.offsetWidth>list.clientWidth)list.scrollLeft+=delta-8;}});}
function renderSortTabs(){
 const labels=lang==='nl'?{newest:'Nieuwste',reads:'Meest gelezen',likes:'Meeste likes'}:{newest:'Latest',reads:'Most read',likes:'Most liked'};
 const select=$('newsSort');select.innerHTML=Object.entries(labels).map(([key,label])=>'<option value="'+key+'">'+label+'</option>').join('');select.value=sort;
 $('filters').querySelectorAll('[data-cat]').forEach(a=>a.href=homePath(a.dataset.cat));if($('mobileTopic'))$('mobileTopic').value=category;
}
function updateFeedNavigation(mode='push'){
 $('filters').querySelectorAll('a[data-cat]').forEach(link=>{
  if(link.dataset.cat===category)link.setAttribute('aria-current','page');else link.removeAttribute('aria-current');
 });
 renderSortTabs();keepSelectedCategoryVisible();
 const path=homePath(category,currentPage)+window.location.hash;
 if(mode!=='none'&&path!==location.pathname+location.search+location.hash)history[mode==='replace'?'replaceState':'pushState'](null,'',path);
 updateFeedMetadata();
}
function updateFeedMetadata(){
 if(isDetail)return;
 const en=lang==='en';
 const meta=seoCatalog[lang][category];
 const title=meta.heading+(currentPage>1?' · '+(en?'Page':'Pagina')+' '+currentPage:'')+' | Jouw Lichtpuntjes';
 document.title=title;
 const canonical=language=>{const u=new URL(homePath(category,currentPage,language),@json(rtrim(config('app.url'),'/')));u.search='';if(currentPage>1)u.searchParams.set('page',String(currentPage));return u.href;};
 document.querySelector('link[rel="canonical"]')?.setAttribute('href',canonical(lang));
 document.querySelector('meta[property="og:url"]')?.setAttribute('content',canonical(lang));
 document.querySelectorAll('meta[property="og:title"],meta[name="twitter:title"]').forEach(el=>el.content=title);
 document.querySelectorAll('meta[name="description"],meta[property="og:description"],meta[name="twitter:description"]').forEach(el=>el.content=meta.description.slice(0,160));
 document.querySelectorAll('link[rel="alternate"][hreflang]').forEach(link=>{const language=link.hreflang==='en'?'en':'nl';link.href=canonical(language);});
 const intro=$('seoCategoryIntro');if(intro){intro.querySelector('h2').textContent=meta.heading;intro.querySelector('p').textContent=meta.description;}
 if(category!=='all')$('headline').textContent=meta.heading;
 const schema=document.querySelector('script[type="application/ld+json"]');
 if(schema){try{const data=JSON.parse(schema.textContent);const node=data['@graph']?.find(n=>n['@type']==='CollectionPage');if(node){node.url=canonical(lang);node['@id']=node.url+'#page';node.name=meta.heading;node.description=meta.description;node.inLanguage=lang;}const crumbs=data['@graph']?.find(n=>n['@type']==='BreadcrumbList');if(crumbs){const home=canonical(lang);const u=new URL(home);u.pathname=lang==='en'?'/en':'/';u.search='';crumbs.itemListElement=[{'@type':'ListItem',position:1,name:lang==='en'?'Home':'Startpagina',item:u.href}];if(category!=='all'){u.pathname=lang==='en'?'/en/topics/'+category:'/nl/onderwerp/'+category;crumbs.itemListElement.push({'@type':'ListItem',position:2,name:ui[lang][category],item:u.href});}}schema.textContent=JSON.stringify(data);}catch(e){}}

}
window.addEventListener('popstate',()=>{
 if(isDetail)return;
 const match=location.pathname.match(/^\/(nl\/onderwerp|en\/topics)\/(community|media|sport|energy|nature|science|people)\/?$/);
 if(!match&&!['/','/en','/en/'].includes(location.pathname))return;
 const nextLang=match?(match[1]==='en/topics'?'en':'nl'):(location.pathname.startsWith('/en')?'en':'nl');
 if(nextLang!==lang){location.reload();return;}
 const params=new URLSearchParams(location.search),nextCategory=match?match[2]:'all';
 const rawPage=params.get('page')||'1',nextPage=/^[1-9]\d*$/.test(rawPage)?Number(rawPage):1;
 const nextSort=['reads','likes'].includes(params.get('sort'))?params.get('sort'):'newest';
 if(nextCategory===category&&nextPage===currentPage&&nextSort===sort)return;
 category=nextCategory;currentPage=nextPage;sort=nextSort;rankDirty=false;updateFeedNavigation('none');loadFeed();
});
function engagementMarkup(d){
    const views=Number.isInteger(d.views_count)?d.views_count:0,likes=Number.isInteger(d.likes_count)?d.likes_count:0;
    const text=lang==='nl'?'gelezen':'reads',label=!signedIn?(lang==='nl'?'Log in om een hartje te geven':'Log in to like'):d.liked?(lang==='nl'?'Like verwijderen':'Remove like'):(lang==='nl'?'Vind ik leuk':'Like this story');
    return '<div class="engagement" data-engagement="'+esc(d.id)+'"><span data-read-count>'+views+' '+text+'</span><button type="button" class="like-button" data-like="'+esc(d.id)+'" aria-pressed="'+!!d.liked+'" aria-label="'+label+'"'+(pendingLikes.has(d.id)?' disabled':'')+'><span aria-hidden="true">'+(d.liked?'♥':'♡')+'</span> '+likes+'</button></div>';
}
async function postEngagement(id,action,body={}){
    const response=await fetch('/news/'+encodeURIComponent(id)+'/'+action+'?lang='+lang,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(body)});
    if(!response.ok){let detail={};try{detail=await response.json()}catch(e){}throw new Error(detail.message||'Engagement unavailable');}return response.json();
}
function paintEngagement(id){
 const d=data.find(d=>d.id===id)||openedStory;if(!d||d.id!==id)return;
 document.querySelectorAll('[data-like]').forEach(b=>{if(b.dataset.like!==id)return;b.disabled=pendingLikes.has(id);b.setAttribute('aria-pressed',String(!!d.liked));b.setAttribute('aria-label',lang==='nl'?(d.liked?'Like verwijderen':'Vind ik leuk'):(d.liked?'Remove like':'Like this story'));b.innerHTML=b.hasAttribute('data-newspaper-like')?newspaperLikeContents(d):'<span aria-hidden="true">'+(d.liked?'♥':'♡')+'</span> '+d.likes_count;});
 document.querySelectorAll('[data-engagement]').forEach(w=>{if(w.dataset.engagement===id){const t=w.querySelector('[data-read-count]');if(t){if(w.classList.contains('newspaper-engagement'))t.innerHTML=newspaperReadContents(d);else t.textContent=d.views_count+' '+(lang==='nl'?'gelezen':'reads');}}});
}
function updateStats(id,stats){
    const d=data.find(d=>d.id===id)||openedStory;if(!d||d.id!==id)return;
    if(Number.isInteger(stats.views_count)&&stats.views_count>=0)d.views_count=stats.views_count;
    if(Number.isInteger(stats.likes_count)&&stats.likes_count>=0)d.likes_count=stats.likes_count;
    if(typeof stats.liked==='boolean')d.liked=stats.liked;
    paintEngagement(id);
}
async function recordRead(id){
    const story=openedStory&&openedStory.id===String(id)?openedStory:data.find(d=>d.id===String(id));
    if(story)document.dispatchEvent(new CustomEvent('lichtpunt:article-open',{detail:{id:String(id),category:story.origin==='community'?'community':story.cat}}));
    try{updateStats(id,await postEngagement(id,'read'));document.dispatchEvent(new CustomEvent('lichtpunt:points-check',{detail:{}}))}catch(e){} // Lezen blijft mogelijk bij een tellerfout.
}
async function setLike(id){
    if(!signedIn){window.location.href='/inloggen?lang='+lang;return;}
    const d=data.find(d=>d.id===id)||openedStory;
    if(!d||d.id!==id||pendingLikes.has(id))return;
    pendingLikes.add(id);actionMessage='';$('actionNotice').textContent='';const notice=$('article').querySelector('[data-article-notice]');if(notice)notice.textContent='';paintEngagement(id);
    try{updateStats(id,await postEngagement(id,'like',{liked:!d.liked}));document.dispatchEvent(new CustomEvent('lichtpunt:points-check',{detail:{}}))}
    catch(e){actionMessage=lang==='nl'?(e.message==='Engagement unavailable'?'Je like kon niet worden opgeslagen. Probeer opnieuw.':e.message):'Your like could not be saved. Check your account and try again.';$('actionNotice').textContent=actionMessage;const notice=$('article').querySelector('[data-article-notice]');if(notice)notice.textContent=actionMessage}
    finally{pendingLikes.delete(id);paintEngagement(id)}
}
$('mobileTopic')?.addEventListener('change',e=>{const next=e.target.value;if(!Object.prototype.hasOwnProperty.call(ui[lang],next)||next===category)return;category=next;currentPage=1;rankDirty=false;updateFeedNavigation();loadFeed();});
$('newsSort').addEventListener('change',e=>{
 const value=e.target.value;if(!['newest','reads','likes'].includes(value)||value===sort)return;
 sort=value;currentPage=1;rankDirty=false;updateFeedNavigation();loadFeed();
});
$('article').addEventListener('click',e=>{const button=e.target.closest('[data-like]');if(button)setLike(button.dataset.like)});
function renderPagination(){
    const nav=$('pagination');
    nav.setAttribute('aria-label',lang==='nl'?'Nieuwspagina’s':'News pages');
    nav.hidden=feedState!=='ready'||pagination.last_page<=1;
    if(nav.hidden){nav.innerHTML='';return}
    const page=pagination.current_page,last=pagination.last_page;
    const numbers=[...new Set([1,last,page-1,page,page+1])].filter(n=>n>=1&&n<=last).sort((a,b)=>a-b);
    const button=(n,label,disabled=false)=>'<a href="'+homePath(category,n)+'" data-page="'+n+'"'+(disabled?' aria-disabled="true"':'')+(n===page&&!disabled?' aria-current="page"':'')+' aria-label="'+(lang==='nl'?'Pagina ':'Page ')+n+'">'+label+'</a>';
    let html=button(Math.max(1,page-1),lang==='nl'?'← Vorige':'← Previous',page===1);
    numbers.forEach((n,i)=>{if(i&&n-numbers[i-1]>1)html+='<span class="gap" aria-hidden="true">…</span>';html+=button(n,n)});
    html+=button(Math.min(last,page+1),lang==='nl'?'Volgende →':'Next →',page===last);
    nav.innerHTML=html;
}
$('pagination').addEventListener('click',async e=>{
    const button=e.target.closest('[data-page]');if(!button)return;if(e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)return;e.preventDefault();if(button.getAttribute('aria-disabled')==='true')return;
    const page=Number(button.dataset.page);if(!Number.isInteger(page)||page<1||page>pagination.last_page||page===currentPage)return;
    currentPage=page;rankDirty=false;updateFeedNavigation();loadFeed();
});
async function loadFeed(){
    mobileHomeLimit=3;
    const version=++feedVersion;
    if(feedController)feedController.abort();feedController=new AbortController();
    const grid=$('grid'),nav=$('pagination');let renderPosition=null;
    if(feedMinHeight===null)feedMinHeight=grid.style.minHeight;
    grid.style.minHeight=grid.getBoundingClientRect().height+'px';
    grid.setAttribute('aria-busy','true');grid.inert=true;nav.inert=true;
    feedState='loading';$('count').textContent=lang==='nl'?'Lichtpuntjes worden geladen…':'Loading bright spots…';
    try{
        const params=new URLSearchParams({page:String(currentPage),category,sort,lang});if(signedIn)params.set('view',newsView);
        const response=await fetch('/news/feed?'+params,{headers:{Accept:'application/json'},signal:feedController.signal});
        if(!response.ok)throw new Error('Feed unavailable');
        const payload=await response.json();if(version!==feedVersion)return;
        const m=payload.pagination;
        if(!Array.isArray(payload.articles)||!m||!Number.isInteger(m.current_page)||!Number.isInteger(m.last_page)||!Number.isInteger(m.total)||m.current_page<1||m.last_page<1||m.total<0||m.per_page!==12)throw new Error('Invalid feed');
        data=payload.articles.filter(d=>{try{const u=new URL(d.url);return ['http:','https:'].includes(u.protocol)&&['community','media','nature','energy','science','people','sport'].includes(d.cat)&&typeof d.source==='string'&&typeof d.id==='string'&&['nl','en'].every(l=>d[l]&&typeof d[l].title==='string'&&typeof d[l].teaser==='string'&&Array.isArray(d[l].body)&&d[l].body.every(p=>typeof p==='string'))}catch(e){return false}});
        pagination=m;currentPage=m.current_page;feedState='ready';renderPosition={x:scrollX,y:scrollY};renderCards();updateFeedNavigation('replace');openSharedStory();
    }catch(e){if(e.name==='AbortError'||version!==feedVersion)return;feedState='error';renderPosition={x:scrollX,y:scrollY};renderCards()}
    finally{
        if(version===feedVersion){
            grid.style.minHeight=feedMinHeight??'';feedMinHeight=null;
            grid.removeAttribute('aria-busy');grid.inert=false;nav.inert=false;
            if(renderPosition)requestAnimationFrame(()=>{if(version===feedVersion&&Math.abs(scrollY-renderPosition.y)>1)window.scrollTo({left:renderPosition.x,top:renderPosition.y,behavior:'instant'});});
        }
    }
}

const startSeenKey='lichtpunt-start-tip-v1';let startShown=false;
// A visit ends after 30 minutes without activity on the homepage.
const startVisitKey='lichtpunt-start-visits-v1',startVisitGap=30*60*1000;
let startVisitEligible=false,startLastActivity=0;
try {
 const saved=JSON.parse(localStorage.getItem(startVisitKey)||'null'),now=Date.now();
 const valid=saved&&Number.isSafeInteger(saved.count)&&saved.count>0&&Number.isFinite(saved.last)&&saved.last>0;
 const count=valid?Math.min(saved.count+(now-saved.last>=startVisitGap?1:0),2):1;
 localStorage.setItem(startVisitKey,JSON.stringify({count,last:now}));startLastActivity=now;
 startVisitEligible=count>=2;
} catch(e) {} // Storage unavailable: keep the manual button, skip automatic prompts.
function keepStartVisitActive(){
 const now=Date.now();if(now-startLastActivity<60000)return;
 try {const saved=JSON.parse(localStorage.getItem(startVisitKey)||'null');
  if(saved&&Number.isSafeInteger(saved.count)&&saved.count>0){localStorage.setItem(startVisitKey,JSON.stringify({count:Math.min(saved.count,2),last:now}));startLastActivity=now;}
 } catch(e) {}
}
['pointerdown','keydown','scroll'].forEach(event=>document.addEventListener(event,keepStartVisitActive,{passive:true}));
function renderStart(){
 const nl=lang==='nl',url=window.location.origin+'/';
 const chrome=nl?['Open Chrome op je computer en ga via ⋮ naar Instellingen.','Kies Bij opstarten → Een specifieke pagina of reeks pagina’s openen.','Klik Een nieuwe pagina toevoegen, plak het adres hieronder en klik Toevoegen.']:['On your computer, open Chrome → ⋮ → Settings.','Choose On startup → Open a specific page or set of pages.','Click Add a new page, paste the address below and click Add.'];
 const safari=nl?['Op je Mac: open Safari → Instellingen → Algemeen.','Plak het adres hieronder in Homepage.','Kies bij Open nieuwe vensters met de optie Homepage. Voor nieuwe tabbladen kan dat ook. Kies bij Safari opent met een nieuw venster als je niet je vorige sessie wilt herstellen.']:['On your Mac, open Safari → Settings → General.','Paste the address below into Homepage.','Set New windows open with to Homepage. You can do the same for new tabs. Set Safari opens with to A new window if you do not want to restore your previous session.'];
 const edge=nl?['Open Edge op je computer en ga via ⋯ naar Instellingen.','Kies Start, startpagina en nieuwe tabbladen → Wanneer Edge wordt gestart.','Kies Deze pagina’s openen, klik Een nieuwe pagina toevoegen en voeg het adres hieronder toe.']:['On your computer, open Edge → ⋯ → Settings.','Choose Start, home, and new tabs → When Edge starts.','Select Open these pages, click Add a new page and add the address below.'];
 const tutorial=(name,steps,help)=>'<details><summary>'+name+'</summary><ol>'+steps.map(x=>'<li>'+esc(x)+'</li>').join('')+'</ol><a href="'+help+'" target="_blank" rel="noopener noreferrer">'+(nl?'Officiële uitleg ↗':'Official guide ↗')+'</a></details>';
 $('startContent').innerHTML='<h2 id="startTitle">'+(nl?'Start je dag positief!':'Start your day with a smile!')+'</h2><p>'+(nl?'Je koffie doet de rest. Wij zorgen voor het goede nieuws. Maak Jouw Lichtpuntjes je startpagina en begin met een glimlach.':'Let your coffee do its thing. We’ll bring the good news. Make Jouw Lichtpuntjes your homepage and start with a smile.')+'</p><div class="start-address"><input id="startAddress" readonly aria-label="'+(nl?'Adres van Jouw Lichtpuntjes':'Jouw Lichtpuntjes address')+'" value="'+esc(url)+'"><button class="primary" id="copyStart">'+(nl?'Kopieer adres':'Copy address')+'</button></div><p id="copyStatus" role="status" class="start-note"></p>'+tutorial('Chrome · '+(nl?'computer':'desktop'),chrome,'https://support.google.com/chrome/answer/95314')+tutorial('Safari · Mac',safari,'https://support.apple.com/guide/safari/ibrw1020/mac')+tutorial('Edge · '+(nl?'computer':'desktop'),edge,'https://support.microsoft.com/en-us/edge/make-the-switch-to-microsoft-edge')+'<p class="start-note">'+(nl?'Dit stel je zelf in je browser in. Op iPhone of iPad kun je Jouw Lichtpuntjes als favoriet of op je beginscherm bewaren.':'You make this change in your browser. On iPhone or iPad, save Jouw Lichtpuntjes as a favourite or add it to your Home Screen.')+'</p><button class="start-dismiss" id="dismissStart">'+(nl?'Eerst de lichtpuntjes lezen →':'Take me to the bright spots →')+'</button>';
 $('closeStart').setAttribute('aria-label',nl?'Sluiten':'Close');
 $('dismissStart').addEventListener('click',()=>$('startDialog').close());
 $('copyStart').addEventListener('click',async()=>{try{await navigator.clipboard.writeText(url);$('copyStatus').textContent=nl?'Adres gekopieerd. Plak het in je browserinstellingen.':'Address copied. Paste it into your browser settings.';}catch(e){$('startAddress').focus();$('startAddress').select();$('copyStatus').textContent=nl?'Kopieer het geselecteerde adres met Ctrl+C of ⌘C.':'Copy the selected address using Ctrl+C or ⌘C.';}});
}
function showStart(manual=false){
 if($('reader').open||$('startDialog').open||$('siteShareDialog').open||$('followUsDialog')?.open||$('momentDetail')?.open)return;
 if(!manual){if(startShown||!startVisitEligible)return;try{if(localStorage.getItem(startSeenKey))return;}catch(e){return;}}
 $('startDialog').showModal();startShown=true;try{localStorage.setItem(startSeenKey,'1');}catch(e){}
}
$('openStart').addEventListener('click',()=>showStart(true));
$('closeStart').addEventListener('click',()=>$('startDialog').close());
setTimeout(()=>{startVisitEligible=true;showStart();},startVisitEligible?0:5*60*1000);
function showPendingStart(){if(!startShown&&startVisitEligible)setTimeout(()=>showStart(),0);}
$('reader').addEventListener('close',showPendingStart);
$('siteShareDialog').addEventListener('close',showPendingStart);
$('followUsDialog').addEventListener('close',showPendingStart);
$('momentDetail')?.addEventListener('close',showPendingStart);


function findThreadComment(id,commentId){const s=threadState[id]||{};return [...(s.comments||[]),...Object.values(s.replies||{}).flatMap(r=>r.comments||[])].find(c=>String(c.id)===String(commentId));}
function threadCommentMarkup(id,c,seen=new Set()){
 if(seen.has(String(c.id)))return '';const branchSeen=new Set(seen);branchSeen.add(String(c.id));
 const nl=lang==='nl',s=threadState[id]||{},branch=s.replies?.[c.id],count=Number.isInteger(c.replies_count)?c.replies_count:0,expanded=!!branch?.expanded;
 const avatar=typeof c.avatar==='string'&&/^\/profielfotos\/[1-9][0-9]*$/.test(c.avatar)?'<img class="comment-avatar" src="'+esc(c.avatar)+'" width="36" height="36" loading="lazy" alt="">':'';
 let out='<div class="comment-item" data-comment-id="'+esc(c.id)+'"><div class="comment-author">'+avatar+'<strong>'+esc(c.name)+'</strong></div><p>'+esc(c.body)+'</p><div class="comment-foot"><small>'+esc(new Intl.DateTimeFormat(nl?'nl-NL':'en-GB',{dateStyle:'medium'}).format(new Date(c.date)))+'</small>';
 if(c.can_heart)out+='<button type="button" class="reply-button" data-comment-heart="'+esc(c.id)+'" aria-pressed="'+!!c.hearted+'" aria-label="'+(nl?'Hartje voor deze reactie':'Heart this comment')+'">'+(c.hearted?'♥':'♡')+' '+Number(c.hearts_count||0)+'</button>';else out+='<span>♥ '+Number(c.hearts_count||0)+'</span>';
 if(signedIn&&s.can_comment)out+='<button type="button" class="reply-button" data-reply="'+esc(c.id)+'"'+(commentSending?' disabled':'')+'>'+(nl?'Reageren':'Reply')+'</button>';
 out+='<a href="/melden/comment/'+encodeURIComponent(c.id)+'?lang='+lang+'">'+(nl?'Melden':'Report')+'</a></div>';
 if(count>0){
  const label=nl?count+' '+(count===1?'antwoord':'antwoorden')+' '+(expanded?'verbergen':'bekijken'):count+' '+(count===1?'reply':'replies')+' '+(expanded?'— hide':'— show');
  out+='<button type="button" class="answers-toggle" data-toggle-replies="'+esc(c.id)+'" aria-expanded="'+expanded+'" aria-controls="answers-'+esc(id)+'-'+esc(c.id)+'"><span aria-hidden="true">'+(expanded?'▾':'▸')+'</span> '+label+'</button><div id="answers-'+esc(id)+'-'+esc(c.id)+'" class="comment-answers"'+(expanded?'':' hidden')+'>';
  if(expanded){
   if(branch.loading)out+='<p role="status">'+(nl?'Antwoorden laden…':'Loading replies…')+'</p>';
   if(branch.error)out+='<p role="status">'+(nl?'Laden lukte niet.':'Could not load replies.')+' <button type="button" class="answers-toggle" data-replies-page="'+(branch.page||1)+'" data-replies-parent="'+esc(c.id)+'">'+(nl?'Opnieuw proberen':'Try again')+'</button></p>';
   out+=(branch.comments||[]).map(reply=>threadCommentMarkup(id,reply,branchSeen)).join('');
   if(branch.last_page>1)out+='<div class="thread-nav">'+(branch.page>1?'<button data-replies-page="'+(branch.page-1)+'" data-replies-parent="'+esc(c.id)+'">← '+(nl?'Vorige':'Previous')+'</button>':'')+'<span>'+branch.page+' / '+branch.last_page+'</span>'+(branch.page<branch.last_page?'<button data-replies-page="'+(branch.page+1)+'" data-replies-parent="'+esc(c.id)+'">'+(nl?'Volgende':'Next')+' →</button>':'')+'</div>';
  }
  out+='</div>';
 }
 return out+'</div>';
}
async function loadReplies(id,parentId,page=1){
 const state=threadState[id]||(threadState[id]={});state.replies||={};const branch=state.replies[parentId]||(state.replies[parentId]={});if(branch.loading)return;
 branch.expanded=true;branch.loading=true;branch.error=false;if(opened===id)renderArticle();
 try{const r=await fetch('/news/'+encodeURIComponent(id)+'/comments?parent_id='+encodeURIComponent(parentId)+'&page='+page,{headers:{Accept:'application/json'}});if(!r.ok)throw Error();const result=await r.json();if(!Array.isArray(result.comments))throw Error();Object.assign(branch,{comments:result.comments,page:result.page,last_page:result.last_page});}
 catch(e){branch.error=true;}finally{branch.loading=false;if(opened===id)renderArticle();}
}
const commentConsentDrafts={};
function compactCommentConsent(id){
 const el=document.createElement('div'),state=commentConsentDrafts[id]||{};
 el.innerHTML=lang==='nl'?@json(view('community.comment-consent',['en'=>false])->render()):@json(view('community.comment-consent',['en'=>true])->render());
 el.querySelectorAll('[name="sensitive_content"]').forEach(input=>{if(input.value===state.content)input.setAttribute('checked','');});
 const permission=el.querySelector('[name="sensitive_consent"]');if(state.content==='self')permission.required=true;if(state.content==='self'&&state.consent)permission.setAttribute('checked','');
 return el.innerHTML;
}
function communityMarkup(id){
 const nl=lang==='nl',s=threadState[id]||{},items=s.comments||[],statusLabels=nl?{pending:'Wacht op keuring',doubt:'De beheerder kijkt mee',rejected:'Niet geplaatst'}:{pending:'Awaiting review',doubt:'Waiting for a human review',rejected:'Not published'};
 let out='<section class="community-thread newspaper-community" aria-label="'+(nl?'Vriendelijke reacties':'Friendly comments')+'"><div class="community-layout"><div class="community-compose"><div class="community-heading"><h3>'+(nl?'Geef een glimlach door':'Pass on a smile')+'</h3></div><p class="community-intro">'+(nl?'Een compliment, een vriendelijke vraag of gewoon iets leuks: je bent welkom.':'A compliment, a friendly question or a little kindness: you are welcome.')+'</p>';
 if(signedIn&&s.can_comment&&replyTargets[id])out+='<div class="reply-target">'+(nl?'Je antwoordt op de reactie van ':'Replying to ')+esc(replyTargets[id].name)+' <button type="button" data-cancel-reply>'+ (nl?'Annuleren':'Cancel')+'</button></div>';
 if(signedIn&&s.can_comment)out+='<form id="commentForm" data-comment="'+esc(id)+'"><label for="commentBody">'+(replyTargets[id]?(nl?'Jouw vriendelijke antwoord':'Your friendly reply'):(nl?'Jouw positieve bijdrage':'Your positive contribution'))+'</label><textarea id="commentBody" name="body" minlength="3" maxlength="1000" required'+(commentSending?' disabled':'')+' placeholder="'+(nl?'Wat maakt jou blij aan dit verhaal?':'What makes you smile about this story?')+'">'+esc(commentDrafts[id]||'')+'</textarea>'+compactCommentConsent(id)+'<div class="community-honeypot" aria-hidden="true"><input name="website" tabindex="-1" autocomplete="off"></div><button class="primary comment-submit"'+(commentSending?' disabled':'')+'>'+(commentSending?(nl?'Je glimlach wordt gekeurd…':'Reviewing your smile…'):(nl?'Verstuur je glimlach ☀':'Send your smile ☀'))+'</button></form>';
 else out+='<p class="community-login"><a href="'+(signedIn?'/profiel':'/inloggen')+'?lang='+lang+'">'+(signedIn?(nl?'Bevestig je e-mail en huisregels om te reageren.':'Confirm your email and community rules to comment.'):(nl?'Log in om een hartje of reactie achter te laten.':'Log in to leave a like or a comment.'))+'</a></p>';
 out+='<p class="comment-message" role="status">'+esc(s.message||'')+'</p>';
 (s.own||[]).forEach(c=>{out+='<div class="comment-item"><small>'+(nl?'Alleen voor jou zichtbaar · ':'Visible only to you · ')+esc(statusLabels[c.status]||'')+'</small><p>'+esc(c.body)+'</p></div>'});
 out+='<details class="community-guidelines"><summary>'+(nl?'Zo houden we het gezellig':'Keeping it friendly')+'</summary><p class="community-rules">'+(nl?'Vier dit verhaal, geef een compliment of stel een vriendelijke vraag. Geen aanvallen, gemopper of meningsdiscussies. We keuren reacties vóór plaatsing.':'Celebrate this story, share appreciation or ask a friendly question. No personal attacks, rants or polarising debates. Comments are reviewed before publication.')+' <a href="/huisregels?lang='+lang+'">'+(nl?'Onze bedoeling':'Our purpose')+'</a></p></details></div><div class="community-conversation">';
 out+='<h4 class="comments-heading">' +(nl?'Reacties':'Comments')+(Number.isInteger(s.total)?' ('+s.total+')':'')+'</h4>';
 if(s.loading&&!items.length)out+='<p>'+(nl?'Glimlachen worden geladen…':'Loading smiles…')+'</p>';
 else if(!items.length)out+='<p>'+(nl?'Nog stil hier. Deel de eerste glimlach.':'No comments yet. Will you bring the first smile?')+'</p>';
 items.forEach(c=>{out+=threadCommentMarkup(id,c)});
 if(s.last_page>1)out+='<div class="thread-nav">'+(s.page>1?'<button data-comments-page="'+(s.page-1)+'">← '+(nl?'Vorige':'Previous')+'</button>':'')+'<span>'+s.page+' / '+s.last_page+'</span>'+(s.page<s.last_page?'<button data-comments-page="'+(s.page+1)+'">'+(nl?'Volgende':'Next')+' →</button>':'')+'</div>';
 return out+'</div></div></section>';
}
async function loadComments(id,page=1){
 const state=threadState[id]||(threadState[id]={});state.replies={};state.loading=true;if(opened===id)renderArticle();
 try{const r=await fetch('/news/'+encodeURIComponent(id)+'/comments?page='+page,{headers:{Accept:'application/json'}});if(!r.ok)throw Error();const result=await r.json();if(!Array.isArray(result.comments)||!Array.isArray(result.own))throw Error();Object.assign(state,result)}catch(e){state.message=lang==='nl'?'Reacties konden niet worden geladen. Open het bericht opnieuw om het nog eens te proberen.':'Comments could not be loaded. Reopen this story to try again.';}finally{state.loading=false;if(opened===id)renderArticle();}
}
$('article').addEventListener('input',e=>{if(e.target.id==='commentBody'&&opened)commentDrafts[opened]=e.target.value;});
$('article').addEventListener('change',e=>{
 const form=e.target.closest('[data-comment]');if(!form||!e.target.name.startsWith('sensitive_'))return;
 const yes=form.elements.sensitive_content.value==='self',consent=form.elements.sensitive_consent;
 consent.required=yes;if(!yes)consent.checked=false;
 commentConsentDrafts[form.dataset.comment]={content:form.elements.sensitive_content.value,consent:consent.checked};
});
$('article').addEventListener('submit',async e=>{
 const form=e.target.closest('[data-comment]');if(!form)return;e.preventDefault();if(commentSending)return;
 const id=form.dataset.comment,body=commentDrafts[id]||form.querySelector('textarea').value;
 const consentFields={sensitive_content:form.elements.sensitive_content.value,sensitive_consent:form.elements.sensitive_consent.checked?'1':'0'};
 commentSending=true;renderArticle();const state=threadState[id]||(threadState[id]={});
 try{const result=await postEngagement(id,'comments',{body,parent_id:replyTargets[id]?.id||null,...consentFields});state.message=lang==='nl'?result.message:({approved:'Your smile has been published! Approved comments earn one bright point, up to five per day.',rejected:'This comment does not fit our friendly community rules and was not published.',doubt:'Your comment is waiting for a human review.',pending:'Your comment is awaiting review.'}[result.status]||'Your comment has been received.');commentDrafts[id]='';delete commentConsentDrafts[id];delete replyTargets[id];await loadComments(id);}catch(e){state.message=lang==='nl'?e.message:'Your comment could not be sent. Check your account and try again.';}finally{commentSending=false;if(opened===id)renderArticle();}
});
function sharedStoryUrl(id){if(id==='site')return window.location.origin+homePath('all');const d=shareTarget(id);return window.location.origin+storyPath(d);}
function shareTarget(id){return id==='site'?{nl:{title:'Jouw Lichtpuntjes — Goed nieuws voor je dagelijkse portie positiviteit'},en:{title:'Jouw Lichtpuntjes — Good news for your daily dose of positivity'}}:data.find(x=>x.id===id)||openedStory;}
function renderSiteShare(){
 $('siteShareTitle').textContent=lang==='nl'?'Deel een beetje positiviteit':'Share a little positivity';
 $('closeSiteShare').setAttribute('aria-label',lang==='nl'?'Sluiten':'Close');
 $('siteShareBody').innerHTML=shareMarkup('site');
}
$('openSiteShare').addEventListener('click',()=>{renderSiteShare();$('siteShareDialog').showModal();});
$('closeSiteShare').addEventListener('click',()=>$('siteShareDialog').close());
$('siteShareDialog').addEventListener('click',e=>{if(e.target===$('siteShareDialog')){const r=$('siteShareDialog').getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)$('siteShareDialog').close();}});
function shareIcon(key){return '<img class="share-icon" src="/images/share/'+key+'.svg?v=paper3" width="48" height="48" alt="" aria-hidden="true">';}
function shareMarkup(id){
 const nl=lang==='nl',url=sharedStoryUrl(id),d=shareTarget(id),site=id==='site',inputId=site?'siteShareUrl':'shareUrl';
 if(!d)return '';
 const text=(nl?'Een lichtpuntje voor jou: ':'A bright spot for you: ')+d[lang].title+'\n'+url;
 const links=[['whatsapp','WhatsApp','https://wa.me/?text='+encodeURIComponent(text)],['facebook','Facebook','https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(url)],['linkedin','LinkedIn','https://www.linkedin.com/sharing/share-offsite/?url='+encodeURIComponent(url)],['x','X','https://x.com/intent/tweet?url='+encodeURIComponent(url)+'&text='+encodeURIComponent((nl?'Een lichtpuntje voor jou: ':'A bright spot for you: ')+d[lang].title)],['email',nl?'E-mail':'Email','mailto:?subject='+encodeURIComponent(d[lang].title)+'&body='+encodeURIComponent(text)]];
 const choices=links.map(([key,label,href])=>'<a data-share-option="'+key+'" data-story="'+esc(id)+'" href="'+esc(href)+'"'+(key==='email'?'':' target="_blank" rel="noopener noreferrer"')+'>'+shareIcon(key)+'<span>'+label+'</span></a>').join('');
 const copyOptions=[['instagram','Instagram'],['tiktok','TikTok'],['copy',nl?'Link kopiëren':'Copy link']];
 const buttons=copyOptions.map(([key,label])=>'<button type="button" data-share-option="'+key+'" data-story="'+esc(id)+'"'+(shareSending?' disabled':'')+'>'+shareIcon(key)+'<span>'+label+'</span>'+(key==='copy'?'':'<small>'+(nl?'Kopieer link':'Copy link')+'</small>')+'</button>').join('');
 const native=typeof navigator.share==='function'?'<button type="button" data-share-option="native" data-story="'+esc(id)+'"'+(shareSending?' disabled':'')+'>'+shareIcon('native')+'<span>'+(nl?'Meer opties':'More options')+'</span></button>':'';
 const local=/\.test$|^localhost$|^127\./.test(new URL(url).hostname);
 return '<div class="share-options" id="'+(site?'siteShareOptions':'shareOptions')+'"'+(site||shareMenuId===id?'':' hidden')+'><div class="share-choices">'+choices+buttons+native+'</div><label class="share-url-label" for="'+inputId+'">'+(nl?'Of kopieer deze link':'Or copy this link')+'</label><input id="'+inputId+'" class="share-url" readonly value="'+esc(url)+'"><p class="share-feedback" role="status" aria-live="polite">'+esc((threadState[id]||{}).shareMessage||'')+'</p>'+(local?'<p class="share-local">'+(nl?'Je test lokaal. Deze link werkt alleen op jouw computer; zodra de site online staat kun je hem met anderen delen.':'You are testing locally. This link only works on your computer; once the site is online you can share it with others.')+'</p>':'')+'</div>';
}
async function copyStoryLink(url,inputId='shareUrl'){
 try{if(navigator.clipboard&&typeof navigator.clipboard.writeText==='function'){await navigator.clipboard.writeText(url);return true;}}catch(e){}
 const input=$(inputId);if(!input)return false;input.focus();input.select();
 try{return typeof document.execCommand==='function'&&document.execCommand('copy');}catch(e){return false;}
}
async function handleShareClick(e){
 const repliesToggle=e.target.closest('[data-toggle-replies]');if(repliesToggle&&opened){const id=opened,parentId=repliesToggle.dataset.toggleReplies,s=threadState[id]||(threadState[id]={});s.replies||={};const branch=s.replies[parentId];if(!branch){await loadReplies(id,parentId);return;}branch.expanded=!branch.expanded;renderArticle();return;}
 const repliesPage=e.target.closest('[data-replies-page]');if(repliesPage&&opened){await loadReplies(opened,repliesPage.dataset.repliesParent,Number(repliesPage.dataset.repliesPage));return;}
 const heart=e.target.closest('[data-comment-heart]');if(heart&&opened){const storyId=opened,c=findThreadComment(storyId,heart.dataset.commentHeart);if(!c||heart.disabled)return;heart.disabled=true;try{const r=await fetch('/comments/'+encodeURIComponent(c.id)+'/heart?lang='+lang,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({liked:!c.hearted})});const result=await r.json();if(!r.ok)throw Error(result.message||'');c.hearted=result.liked;c.hearts_count=result.hearts_count;}catch(error){threadState[storyId].message=error.message||(lang==='nl'?'Het hartje kon niet worden opgeslagen.':'Your heart could not be saved.');}finally{heart.disabled=false;if(opened===storyId)renderArticle();}return;}
 const reply=e.target.closest('[data-reply]');if(reply&&opened){if(commentSending)return;const c=findThreadComment(opened,reply.dataset.reply);if(c){replyTargets[opened]={id:String(c.id),name:c.name};renderArticle();const input=$('commentBody');if(input){input.focus();input.scrollIntoView({block:'nearest',behavior:'auto'});}}return;}
 if(e.target.closest('[data-cancel-reply]')&&opened){if(commentSending)return;delete replyTargets[opened];renderArticle();return;}

 const page=e.target.closest('[data-comments-page]');if(page&&opened){loadComments(opened,Number(page.dataset.commentsPage));return;}
 const toggle=e.target.closest('[data-share]');
 if(toggle){if(shareSending)return;shareMenuId=shareMenuId===toggle.dataset.share?null:toggle.dataset.share;renderArticle();const trigger=$('article').querySelector('[data-share]');if(trigger)trigger.focus();return;}
 const option=e.target.closest('[data-share-option]');if(!option)return;
 if(shareSending){e.preventDefault();return;}
 const id=option.dataset.story,d=shareTarget(id);if(!d)return;
 const action=option.dataset.shareOption,url=sharedStoryUrl(id),nl=lang==='nl',s=threadState[id]||(threadState[id]={});
 if(!['copy','native','whatsapp','facebook','linkedin','x','email','instagram','tiktok'].includes(action))return;
 shareSending=true;
 try{
  if(['copy','instagram','tiktok'].includes(action)){
   e.preventDefault();if(!await copyStoryLink(url,id==='site'?'siteShareUrl':'shareUrl')){s.shareMessage=nl?'Selecteer de link en kopieer hem met Ctrl+C, Cmd+C of het kopieermenu.':'Select the link and copy it with Ctrl+C, Cmd+C or the copy menu.';return;}
   s.shareMessage=action==='instagram'?(nl?'Link gekopieerd. Open Instagram en plak hem in een privébericht of gebruik de linksticker in je story.':'Link copied. Open Instagram and paste it into a direct message or use the link sticker in your story.'):action==='tiktok'?(nl?'Link gekopieerd. Open TikTok en plak hem waar je een link kunt delen. Een nieuwslink wordt geen video.':'Link copied. Open TikTok and paste it where you can share a link. A news link does not become a video.'):(nl?'Link gekopieerd. Geef een glimlach door!':'Link copied. Pass on a smile!');
  }else if(action==='native'){
   e.preventDefault();await navigator.share({title:d[lang].title,text:nl?'Een lichtpuntje voor jou ☀':'A bright spot for you ☀',url});
   s.shareMessage=nl?'Geef een glimlach door!':'Pass on a smile!';
  }else{
   // The ordinary link opens the selected app; its final send action remains with the visitor.
   s.shareMessage=nl?'Je deeloptie is geopend. Kies zelf met wie je dit lichtpuntje deelt.':'Your sharing option is open. Choose who to share this bright spot with.';
  }
  if(id!=='site'&&signedIn&&s.can_comment){
   try{const result=await postEngagement(id,'share');document.dispatchEvent(new CustomEvent('lichtpunt:points-check',{detail:{}}));if(result.awarded)s.shareMessage+=' '+(nl?'Een lichtpunt voor delen!':'A bright point for sharing!');}catch(e){s.shareMessage+=' '+(nl?'Je lichtpunt kon nu niet worden bijgewerkt.':'Your bright point could not be updated right now.');}
  }
 }catch(e){if(e.name!=='AbortError')s.shareMessage=nl?'Deze deeloptie is niet beschikbaar. Kies WhatsApp, e-mail of kopieer de link.':'This sharing option is unavailable. Choose WhatsApp, email or copy the link.';}
 finally{shareSending=false;if(id==='site'||opened===id){if(id==='site')renderSiteShare();else renderArticle();if(['copy','instagram','tiktok'].includes(action)&&s.shareMessage.startsWith(nl?'Selecteer':'Select')){const input=$(id==='site'?'siteShareUrl':'shareUrl');if(input){input.focus();input.select();}}}}
}
$('article').addEventListener('click',handleShareClick);
$('siteShareBody').addEventListener('click',handleShareClick);
document.addEventListener('click',async e=>{
 if(e.target.closest('details,summary,.ai-image-label'))return;
 const target=e.target.closest('[data-feature-story],[data-feature-card]');const link=target?(target.matches('[data-feature-story]')?target:target.querySelector('[data-feature-story]')):null;if(!link||e.ctrlKey||e.metaKey||e.shiftKey||e.altKey||e.button>0)return;
 e.preventDefault();const id=link.dataset.featureStory;
 try{const response=await fetch('/news/'+encodeURIComponent(id)+'/story',{headers:{Accept:'application/json'}});if(!response.ok)throw Error('Unavailable');const d=await response.json();if(typeof d.id!=='string')throw Error('Invalid');opened=d.id;openedStory=d;renderArticle();if(!$('reader').open)$('reader').showModal();recordRead(d.id);loadComments(d.id);}
 catch(error){window.location.assign(link.href);}
});
async function openSharedStory(){
 if(deepLinkOpened)return;deepLinkOpened=true;const id=new URLSearchParams(window.location.search).get('story');if(!id||!/^\d+$/.test(id))return;
 try{const r=await fetch('/news/'+id+'/story',{headers:{Accept:'application/json'}});if(!r.ok)return;const d=await r.json();if(typeof d.id!=='string')return;opened=id;openedStory=d;renderArticle();if(!$('startDialog').open)$('reader').showModal();recordRead(id);loadComments(id);}catch(e){}
}

if(isDetail){recordRead(detailStory.id);loadComments(detailStory.id);}

function lightspotReportMarkup(id){return '<p class="newspaper-report"><a href="/melden/article/'+encodeURIComponent(id)+'?lang='+lang+'">'+(lang==='nl'?'Bijdrage melden':'Report contribution')+'</a></p>';}
document.addEventListener('submit',async e=>{const f=e.target.closest('form[data-report-story]');if(!f)return;e.preventDefault();const button=f.querySelector('button'),message=f.querySelector('[role=status]');button.disabled=true;try{const r=await fetch('/news/'+encodeURIComponent(f.dataset.reportStory)+'/melden?lang='+lang,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({note:f.elements.note.value})});const data=await r.json();if(!r.ok)throw Error(data.message||'');message.textContent=data.message;}catch(error){message.textContent=lang==='nl'?'Melden lukt nog niet. Controleer je e-mailbevestiging en probeer later opnieuw.':'Reporting failed. Check your email verification and try later.';}finally{button.disabled=false;}});
</script>
<script nonce="{{ request()->attributes->get('csp_nonce') }}" src="/js/moment-washing-line.js?v={{ filemtime(public_path('js/moment-washing-line.js')) }}" defer></script>
@include('shared.cookies')
@include('shared.lightpoint-notice')

<script src="/js/moment-hearts.js?v={{ filemtime(public_path('js/moment-hearts.js')) }}" defer nonce="{{ request()->attributes->get('csp_nonce') }}"></script>

</body></html>
