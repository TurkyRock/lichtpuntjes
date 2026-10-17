(()=>{
'use strict';const box=document.getElementById('lightpoint-notice');if(!box)return;
const en=box.dataset.language==='en';let busy=false,queued=false,closeTimer,pollTimer,stopped=false;
function hide(){clearTimeout(closeTimer);box.classList.remove('is-visible');if(typeof box.hidePopover==='function')box.hidePopover();}
function show(n){
 if(!n||!Number.isInteger(n.points)||n.points<=0)return;
 box.querySelector('[data-point-title]').textContent='+'+n.points+' '+(en?(n.points===1?'bright point':'bright points'):(n.points===1?'lichtpuntje':'lichtpuntjes'));
 box.querySelector('[data-point-label]').textContent=typeof n.label==='string'?n.label:'';
 const badge=box.querySelector('[data-point-badge]');badge.replaceChildren();badge.hidden=true;box.classList.remove('has-badge');
 if(n.badge&&typeof n.badge.name==='string'&&/^\/images\/badges\/[a-z]+\.svg$/.test(n.badge.icon)){
  const img=document.createElement('img');img.src=n.badge.icon;img.alt='';badge.append(img,document.createTextNode((en?'New badge: ':'Nieuwe badge: ')+n.badge.name));badge.hidden=false;box.classList.add('has-badge');
 }
 box.classList.add('is-visible');if(typeof box.showPopover==='function')box.showPopover();
 clearTimeout(closeTimer);closeTimer=setTimeout(hide,n.badge?14000:8500);
}
async function check(){
 if(document.hidden||stopped)return;if(busy){queued=true;return;}busy=true;
 try{const r=await fetch('/lichtpuntjes/meldingen?lang='+encodeURIComponent(box.dataset.language),{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-CSRF-TOKEN':box.dataset.token}});
  if([401,403,419].includes(r.status)){stopped=true;clearInterval(pollTimer);return;}
  if(r.ok){const d=await r.json();show(d.notice);}
 }catch(e){}finally{busy=false;if(queued){queued=false;check();}}
}
box.querySelector('button').addEventListener('click',hide);
box.addEventListener('mouseenter',()=>clearTimeout(closeTimer));box.addEventListener('focusin',()=>clearTimeout(closeTimer));
box.addEventListener('mouseleave',()=>{closeTimer=setTimeout(hide,5000);});box.addEventListener('focusout',()=>{closeTimer=setTimeout(hide,5000);});
document.addEventListener('visibilitychange',()=>{if(!document.hidden)check();});
document.addEventListener('lichtpunt:points-check',check);
window.LichtpuntNotice={check};pollTimer=setInterval(check,20000);check();
})();
