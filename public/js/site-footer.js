(()=>{
 'use strict';const footer=document.querySelector('.site-footer-paper');if(!footer)return;
 const update=()=>{
  const en=document.documentElement.lang==='en',lang=en?'en':'nl';
  const tagline=document.querySelector('.mobile-header-tagline');if(tagline)tagline.textContent=en?'your daily dose of positive news':'jouw dagelijkse dosis positief nieuws';const greeting=document.querySelector('.mobile-daily-greeting>span');if(greeting)greeting.textContent=en?'A little light for today':'Een beetje licht voor vandaag';
  footer.setAttribute('aria-label',en?'Website information':'Website-informatie');
  footer.querySelector('.footer-links').setAttribute('aria-label',en?'About the website':'Over de website');
  footer.querySelector('.footer-legal').setAttribute('aria-label',en?'Privacy and rules':'Privacy en afspraken');
  footer.querySelectorAll('[data-footer-nl]').forEach(e=>{e.textContent=en?e.dataset.footerEn:e.dataset.footerNl;});
  footer.querySelectorAll('[data-footer-href-nl]').forEach(e=>{e.href=en?e.dataset.footerHrefEn:e.dataset.footerHrefNl;});
  footer.querySelectorAll('[data-footer-query]').forEach(e=>{const url=new URL(e.getAttribute('href'),location.href);url.searchParams.set('lang',lang);e.href=url.pathname+url.search;});
  footer.querySelector('[data-footer-home]').href=en?'/en':'/';
 };
 new MutationObserver(update).observe(document.documentElement,{attributes:true,attributeFilter:['lang']});update();
})();
