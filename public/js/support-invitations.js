(()=>{
 'use strict';
 const slot=document.querySelector('[data-support-invitation]');if(!slot)return;
 const kind=slot.dataset.kind,placement=slot.dataset.placement,card=slot.querySelector('.support-invitation-card'),dialog=slot.querySelector('dialog'),storageKey='lichtpunt-support-dismissed:'+slot.dataset.scope;
 let claimed=false,claiming=false,dismissed=false,timer=null,gridObserver=null;
 const storedPause=()=>{try{return Number(sessionStorage.getItem(storageKey))>Date.now();}catch{return false;}};
 const post=async(action,data={})=>{const response=await fetch('/steun/uitnodiging/'+action,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':slot.dataset.token},body:JSON.stringify(data)});if(!response.ok)throw Error('Invitation unavailable');return response.json();};
 const remove=()=>{dismissed=true;clearTimeout(timer);gridObserver?.disconnect();slot.remove();};
 const dismiss=()=>{
  if(dismissed)return;dismissed=true;clearTimeout(timer);gridObserver?.disconnect();
  try{sessionStorage.setItem(storageKey,String(Date.now()+90*86400000));}catch{}
  post('sluiten').catch(()=>{});
  if(dialog?.open)dialog.close();
  if(matchMedia('(prefers-reduced-motion: reduce)').matches||!slot.animate){slot.remove();return;}
  const height=slot.getBoundingClientRect().height;slot.style.overflow='hidden';
  const animation=slot.animate([{height:height+'px',opacity:1,marginTop:getComputedStyle(slot).marginTop,marginBottom:getComputedStyle(slot).marginBottom},{height:'0px',opacity:0,marginTop:'0px',marginBottom:'0px'}],{duration:180,easing:'ease-out',fill:'forwards'});
  animation.finished.then(()=>slot.remove()).catch(()=>slot.remove());
 };
 if(storedPause()){remove();return;}
 slot.addEventListener('click',event=>{if(event.target.closest('[data-dismiss-support]')){event.preventDefault();dismiss();}});
 const claim=async()=>{
  if(dismissed||storedPause()){remove();return false;}if(claimed)return true;if(claiming)return false;claiming=true;
  try{const result=await post('getoond',{kind,placement});if(result.show!==true){remove();return false;}claimed=true;return true;}
  catch{remove();return false;}finally{claiming=false;}
 };
 if(kind==='inline'){
  const grid=placement==='home'?document.getElementById('grid'):null;
  if(grid){const move=()=>{if(dismissed)return;const cards=[...grid.children].filter(e=>e.classList.contains('card'));const target=cards[Math.min(3,cards.length-1)];if(target){if(slot.previousElementSibling!==target)target.after(slot);}else if(slot.parentElement!==grid)grid.append(slot);};move();gridObserver=new MutationObserver(move);gridObserver.observe(grid,{childList:true});}
  const reveal=async()=>{if(await claim())card.hidden=false;};
  if('IntersectionObserver'in window){const observer=new IntersectionObserver(entries=>{if(entries.some(e=>e.isIntersecting)){observer.disconnect();reveal();}},{rootMargin:'120px'});observer.observe(slot);}else reveal();
 }else if(dialog&&typeof dialog.showModal==='function'){
  const started=Date.now();let read=false;
  const show=async()=>{clearTimeout(timer);if(dismissed||dialog.open)return;const busy=document.hidden||document.querySelector('dialog[open]')||document.querySelector('#lpCookiePanel:not([hidden])')||document.activeElement?.matches('input,textarea,select,[contenteditable="true"]');if(busy){timer=setTimeout(show,15000);return;}if(await claim())dialog.showModal();};
  timer=setTimeout(show,60000);
  document.addEventListener('lichtpunt:article-open',()=>{read=true;});
  document.getElementById('reader')?.addEventListener('close',()=>{if(read){clearTimeout(timer);timer=setTimeout(show,Math.max(1500,20000-(Date.now()-started)));}});
  dialog.addEventListener('close',()=>{if(!dismissed)dismiss();});
  dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dismiss();});
 }
})();
