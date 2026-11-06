// Eén artikelopbouw voor de openbare pagina én de leespopup.
function newspaperLikeContents(d){
 const likes=Number.isInteger(d.likes_count)?d.likes_count:0,nl=lang==='nl';
 return '<span class="newspaper-like-inner"><span class="newspaper-heart" aria-hidden="true">'+(d.liked?'♥':'♡')+'</span><span>'+(d.liked?(nl?'Hartje gegeven':'Heart given'):(nl?'Geef een hartje':'Give a heart'))+'</span><span class="newspaper-number">'+new Intl.NumberFormat(nl?'nl-NL':'en-GB').format(likes)+'</span></span>';
}
function newspaperReadContents(d){
 const views=Number.isInteger(d.views_count)?d.views_count:0;
 return '<span class="newspaper-number">'+new Intl.NumberFormat(lang==='nl'?'nl-NL':'en-GB').format(views)+'</span><span>'+(lang==='nl'?'keer gelezen':'reads')+'</span>';
}
function newspaperEngagement(d){
 const nl=lang==='nl',label=!signedIn?(nl?'Log in om een hartje te geven':'Log in to give a heart'):d.liked?(nl?'Hartje verwijderen':'Remove heart'):(nl?'Geef een hartje':'Give a heart');
 return '<div class="newspaper-engagement" data-engagement="'+esc(d.id)+'"><button type="button" class="like-button newspaper-like" data-newspaper-like data-like="'+esc(d.id)+'" aria-pressed="'+!!d.liked+'" aria-label="'+label+'"'+(pendingLikes.has(d.id)?' disabled':'')+'>'+newspaperLikeContents(d)+'</button><span class="newspaper-views"><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg><span data-read-count aria-live="polite">'+newspaperReadContents(d)+'</span></span></div>';
}
function newspaperRelatedMarkup(d){
 const candidates=isDetail?detailRelated:data,items=candidates.filter(item=>item.id!==d.id&&item[lang]&&Array.isArray(item[lang].body)).slice(0,3);if(!items.length)return '';
 return '<aside class="newspaper-related"><h3>'+(lang==='nl'?'Meer lichtpuntjes om te ontdekken':'More bright spots to discover')+'</h3><div class="newspaper-related-grid">'+items.map(item=>'<a class="newspaper-related-card" href="'+esc(storyPath(item))+'" data-related-story="'+esc(item.id)+'">'+(usablePhoto(item)?'<img src="'+esc(item.photo.url)+'" alt="" loading="lazy" width="100" height="82">':'<span class="related-paper-sun" aria-hidden="true">☀</span>')+'<span><strong>'+esc(item[lang].title)+'</strong><span>'+(lang==='nl'?'Lees dit lichtpuntje →':'Read this bright spot →')+'</span></span></a>').join('')+'</div></aside>';
}
function renderArticle(){
 const d=data.find(d=>d.id===opened)||openedStory;if(!d)return;
 const nl=lang==='nl',own=d.origin==='community',t=ui[lang],target=$('article'),same=target.dataset.newspaperId===String(d.id),active=document.activeElement;
 if(!same)actionMessage='';
 const keepFocus=same&&target.contains(active)&&active.id==='commentBody',selection=keepFocus?[active.selectionStart,active.selectionEnd]:null,readerTop=$('reader').scrollTop;
 const sourceUrl=safeWebUrl(d.url),by=own?'<span>'+(nl?'Van onze community':'From our community')+'</span><strong>'+esc(d.source)+'</strong>':(sourceUrl?'<a href="'+sourceUrl+'" target="_blank" rel="noopener noreferrer">'+esc(d.source)+' ↗</a>':'<strong>'+esc(d.source)+'</strong>');
 const crumb='<nav class="story-breadcrumb" aria-label="'+(nl?'Kruimelpad':'Breadcrumb')+'"><a href="'+homePath('all')+'">← '+(nl?'Terug naar lichtpuntjes':'Back to bright spots')+'</a><a class="news-category" href="'+homePath(d.cat)+'">'+esc(t[d.cat]||d.cat)+'</a></nav>';
 const photo=photoMarkup(d,true).replace('class="article-photo"','class="article-photo newspaper-photo"');
 const source=!own&&sourceUrl?'<div class="sourcebox"><a class="primary" href="'+sourceUrl+'" target="_blank" rel="noopener noreferrer" aria-label="'+t.source+' — '+t.external+'">'+t.source+'</a></div>':'';
 const share='<button type="button" class="share-story newspaper-share-toggle" aria-controls="shareOptions" aria-expanded="'+(shareMenuId===d.id)+'" data-share="'+esc(d.id)+'"'+(shareSending?' disabled':'')+'><svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 3 6 6-6 6V11C8 11 5 13 3 19c0-9 4-13 12-13Z"/></svg>'+(nl?'Deel dit lichtpuntje':'Share this bright spot')+'</button>';
 $('readerCat').textContent=t[d.cat]||d.cat;
 target.innerHTML='<div class="newspaper-story" data-newspaper-story><article class="newspaper-article">'+crumb+'<'+(isDetail?'h1':'h2')+' class="newspaper-title" id="articleTitle">'+esc(d[lang].title)+'</'+(isDetail?'h1':'h2')+'><div class="article-source">'+by+'<span aria-hidden="true">·</span><time datetime="'+esc(d.date||'')+'">'+date(d.date)+'</time></div><div class="newspaper-lead'+(photo?'':' news-without-photo')+'">'+photo+'<div class="newspaper-copy">'+d[lang].body.map(p=>'<p>'+esc(p)+'</p>').join('')+source+'</div></div><div class="newspaper-ribbon">'+newspaperEngagement(d)+share+shareMarkup(d.id)+'<p class="action-notice" data-article-notice role="status">'+esc(actionMessage)+'</p></div></article>'+communityMarkup(d.id)+(own?lightspotReportMarkup(d.id):'')+newspaperRelatedMarkup(d)+'</div>';
 target.dataset.newspaperId=d.id;
 if(same)$('reader').scrollTop=readerTop;else $('reader').scrollTop=0;
 if(keepFocus&&$('commentBody')){const field=$('commentBody');field.focus({preventScroll:true});field.setSelectionRange(...selection);}
}
