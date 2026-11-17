(()=>{
 'use strict';const notice=document.getElementById('lightpoint-notice');if(!notice)return;
 const en=notice.dataset.language==='en',grid=document.querySelector('.badge-path-grid');
 let state=null,visibleGrid=false,startedId=-1,completedId=-1,accountDone=-1,ackBusy=false,target=null;
 const observer=grid&&'IntersectionObserver'in window?new IntersectionObserver(entries=>{visibleGrid=entries[0].isIntersecting;if(visibleGrid)observePage();},{threshold:0.2}):null;
 function marker(link,points,label){
  link.classList.toggle('has-unread-lightpoints',points>0);
  let tag=link.querySelector('.lightpoint-attention-count');
  if(points>0){if(!tag){tag=document.createElement('span');tag.className='lightpoint-attention-count';link.append(tag);}tag.textContent=label;tag.setAttribute('aria-label',en?points+' new bright points':points+' nieuwe lichtpunten');}
  else tag?.remove();
 }
 function update(next){
  if(!next||!Number.isSafeInteger(next.event_id)||!Number.isFinite(next.total))return;
  state=next;
  if(grid&&next.badge_new_points>0){
   const steps=[...grid.querySelectorAll('.badge-path-step')],current=[...steps].reverse().find(e=>Number(e.dataset.badgePoints)<=next.total)||steps[0];
   const banner=document.querySelector('.badge-progress-update');
   if(banner&&current){banner.hidden=false;banner.querySelector('[data-badge-update-title]').textContent=next.new_badges?.length?(en?'You have earned a new badge!':'Wat straal jij vandaag!'):(en?'Your light trail has grown.':'Je lichtspoor is weer gegroeid.');
    const following=steps.find(e=>Number(e.dataset.badgePoints)>next.total);banner.querySelector('[data-badge-update-detail]').textContent=next.total+(en?' bright points. ':' lichtpunten. ')+(following?(en?'Another ':'Nog ')+(Number(following.dataset.badgePoints)-next.total)+(en?' to ':' tot ')+following.dataset.badgeName+'.':(en?'Every badge earned!':'Alle badges behaald!'));
    banner.querySelector('[data-badge-update-link]').href='#'+current.id;}
   if(current&&current!==target){observer?.disconnect();target=current;visibleGrid=false;if(observer)observer.observe(current);else visibleGrid=true;}
  }
  document.querySelectorAll('a[href]').forEach(link=>{
   let url;try{url=new URL(link.getAttribute('href'),location.href);}catch(e){return;}
   if(url.origin!==location.origin||url.pathname!=='/profiel')return;
   if(link.closest('.account-sidebar')&&url.searchParams.get('section')==='badges')marker(link,Math.max(0,next.badge_new_points||0),en?'New':'Nieuw');
   else if(link.closest('.site-header')||link.hasAttribute('data-lightpoint-account'))marker(link,Math.max(0,next.account_new_points||0),'+'+(next.account_new_points||0));
  });
  document.querySelectorAll('#siteMenuToggle').forEach(button=>marker(button,Math.max(0,next.account_new_points||0),'+'+(next.account_new_points||0)));
  observePage();
 }
 async function acknowledge(section,id){
  if(ackBusy||document.hidden)return;ackBusy=true;let success=false;
  try{
   const r=await fetch('/lichtpuntjes/voortgang/bekeken?lang='+encodeURIComponent(notice.dataset.language),{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':notice.dataset.token},body:JSON.stringify({section,event_id:id})});
   if(r.ok){const data=await r.json();accountDone=Math.max(accountDone,id);success=true;update(data.attention);}
  }catch(e){}finally{ackBusy=false;if(success)observePage();}
 }
 function observePage(){
  if(!state||document.hidden)return;
  if(document.querySelector('.account-content:not(.admin-content)')&&state.account_new_points>0&&accountDone<state.event_id&&!ackBusy){acknowledge('account',state.event_id);return;}
  if(grid&&visibleGrid&&state.badge_new_points>0&&!ackBusy){
   if(completedId>=state.event_id){acknowledge('badges',state.event_id);return;}
   if(startedId!==state.event_id){startedId=state.event_id;grid.dataset.pendingAttention=JSON.stringify(state);document.dispatchEvent(new CustomEvent('lichtpunt:badge-attention',{detail:state}));}
  }
 }
 document.addEventListener('lichtpunt:badge-animation-finished',event=>{
  if(!Number.isSafeInteger(event.detail?.event_id))return;completedId=Math.max(completedId,event.detail.event_id);
  if(visibleGrid&&!document.hidden)acknowledge('badges',event.detail.event_id);
 });
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)observePage();});
 window.LichtpuntProgress={update};
 try{update(JSON.parse(notice.dataset.progressState));}catch(e){}
})();
