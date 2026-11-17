(()=>{
 'use strict';const grid=document.querySelector('.badge-path-grid');if(!grid)return;
 const ns='http://www.w3.org/2000/svg',svg=document.createElementNS(ns,'svg');svg.classList.add('badge-path-route');svg.setAttribute('aria-hidden','true');svg.setAttribute('focusable','false');
 const make=(tag,name)=>{const e=document.createElementNS(ns,tag);e.classList.add(name);svg.append(e);return e;};
 const base=make('path','badge-path-base-route'),old=make('path','badge-path-earned-route'),glow=make('path','badge-path-progress-route'),orb=make('circle','badge-path-light-orb');orb.setAttribute('r','5');old.style.display=glow.style.display=orb.style.display='none';grid.append(svg);
 const steps=[...grid.querySelectorAll('.badge-path-step')],reduced=matchMedia('(prefers-reduced-motion:reduce)');let frame=null,animationFrame=null,segments=[],totalLength=0,pending=null,animationStarted=0,completed=false;
 const en=document.documentElement.lang==='en';
 function geometry(){
  const box=grid.getBoundingClientRect();if(!box.width||!box.height)return;
  svg.setAttribute('viewBox',`0 0 ${box.width} ${box.height}`);
  const items=steps.map(e=>{const r=e.getBoundingClientRect(),disc=e.querySelector('.badge-path-disc').getBoundingClientRect();return{x:disc.x-box.x+disc.width/2,y:disc.y-box.y+disc.height/2,top:r.top-box.top,bottom:r.bottom-box.top};});
  let d='';segments=[];totalLength=0;
  for(let i=1;i<items.length;i++){
   const a=items[i-1],b=items[i];let segment;
   if(Math.abs(a.top-b.top)<3)segment=`M${a.x},${a.y}H${b.x}`;
   else{const mid=(a.bottom+b.top)/2,right=box.width-3,left=3,r=7;segment=`M${a.x},${a.y}H${right-r}Q${right},${a.y} ${right},${a.y+r}V${mid-r}Q${right},${mid} ${right-r},${mid}H${left+r}Q${left},${mid} ${left},${mid+r}V${b.y-r}Q${left},${b.y} ${left+r},${b.y}H${b.x}`;}
   const measure=document.createElementNS(ns,'path');measure.setAttribute('d',segment);svg.append(measure);const length=measure.getTotalLength();measure.remove();
   segments.push({from:Number(steps[i-1].dataset.badgePoints),to:Number(steps[i].dataset.badgePoints),start:totalLength,length});totalLength+=length;d+=i===1?segment:segment.slice(segment.indexOf('H'));
  }
  [base,old,glow].forEach(p=>p.setAttribute('d',d));
  if(pending){if(completed)paint(1);else if(animationStarted)paint(Math.min(1,(performance.now()-animationStarted)/2400));}
 }
 function distance(points){for(const s of segments){if(points<s.to)return s.start+s.length*Math.max(0,(points-s.from)/(s.to-s.from));}return totalLength;}
 function refreshBadges(state){
  const total=state.total;let current=steps[0],next=null;
  steps.forEach(step=>{const earned=Number(step.dataset.badgePoints)<=total;step.classList.toggle('is-earned',earned);step.classList.toggle('is-locked',!earned);if(earned)current=step;else if(!next)next=step;});
  steps.forEach(step=>{step.classList.toggle('is-current',step===current);step.classList.toggle('is-next',step===next);if(step===current)step.setAttribute('aria-current','step');else step.removeAttribute('aria-current');const label=step.querySelector('.badge-path-state');if(label)label.textContent=step===current?(en?'You are here':'Hier sta je'):step===next?(en?'Next badge':'Volgende badge'):step.classList.contains('is-earned')?(en?'Unlocked':'Vrijgespeeld'):(en?'Still locked':'Nog op slot');});
  const summary=document.querySelector('.badge-path-summary');if(!summary)return;
  summary.querySelector('.badge-path-total strong').textContent=String(total);
  summary.querySelector('.badge-path-count strong').replaceChildren(document.createTextNode(steps.filter(s=>Number(s.dataset.badgePoints)<=total).length+' '),Object.assign(document.createElement('span'),{textContent:en?'of':'van'}),document.createTextNode(' '+steps.length));
  for(const [selector,step]of [['.badge-path-current',current],['.badge-path-next',next]]){
   const target=summary.querySelector(selector);if(!target)continue;
   if(step){let badge=target.querySelector('.paper-badge');if(!badge){badge=document.createElement('span');target.prepend(badge);}badge.className='paper-badge paper-badge--'+step.dataset.badgeId;badge.setAttribute('aria-hidden','true');let copy=target.querySelector('div');if(!copy){copy=document.createElement('div');target.append(copy);}const label=document.createElement('span');label.textContent=selector.includes('current')?(en?'Your current badge':'Jouw huidige badge'):(en?'Next badge':'Volgende badge');const name=document.createElement('strong');name.textContent=step.dataset.badgeName;copy.replaceChildren(label,name);if(step===next){const remain=document.createElement('span');remain.textContent=(en?'Another ':'Nog ')+(Number(step.dataset.badgePoints)-total)+(en?' bright points':' lichtpunten');copy.append(remain);}}
   else{target.replaceChildren(document.createTextNode(en?'Every badge earned!':'Alle badges behaald!'));}
  }
  const progress=summary.querySelector('progress');if(progress){const percent=next?100*(total-Number(current.dataset.badgePoints))/(Number(next.dataset.badgePoints)-Number(current.dataset.badgePoints)):100;progress.value=percent;progress.setAttribute('aria-valuetext',Math.round(percent)+(en?'% towards your next badge':'% op weg naar je volgende badge'));}
 }
 function paint(fraction){
  if(!pending||!segments.length)return;
  const previous=Math.min(pending.total,Math.max(0,pending.previous_total));
  const start=distance(previous),end=distance(pending.total),ease=1-Math.pow(1-Math.max(0,Math.min(1,fraction)),2),position=start+(end-start)*ease;
  const active=segments.find(s=>position<s.start+s.length-.01)||segments[segments.length-1],before=active.start;
  old.style.display=glow.style.display=orb.style.display='';old.style.strokeDasharray=before+' '+(totalLength+1);
  glow.style.strokeDasharray=Math.max(0,position-before)+' '+(totalLength+1);glow.style.strokeDashoffset=String(-before);
  const point=base.getPointAtLength(position);orb.setAttribute('cx',point.x);orb.setAttribute('cy',point.y);
  steps.forEach(step=>{if(!(pending.new_badges||[]).includes(step.dataset.badgeId)||distance(Number(step.dataset.badgePoints))>position+.5)return;if(!step.classList.contains('badge-path-newly-earned')){step.classList.add('badge-path-newly-earned');const label=document.createElement('span');label.className='badge-path-new-label';label.textContent=en?'New!':'Nieuw!';step.append(label);}});
 }
 function finish(){completed=true;paint(1);document.dispatchEvent(new CustomEvent('lichtpunt:badge-animation-finished',{detail:{event_id:pending.event_id}}));}
 function animate(state){
  if(!state||!Number.isSafeInteger(state.event_id)||!Number.isFinite(state.total)||!Number.isFinite(state.previous_total))return;
  cancelAnimationFrame(animationFrame);pending=state;completed=false;refreshBadges(state);geometry();
  if(!segments.length)return;
  if(reduced.matches){finish();return;}
  animationStarted=performance.now();
  const tick=time=>{const t=Math.min(1,(time-animationStarted)/2400);paint(t);if(t<1)animationFrame=requestAnimationFrame(tick);else finish();};animationFrame=requestAnimationFrame(tick);
 }
 const initial=Number(document.querySelector('.badge-path-total strong')?.textContent.trim());
 if(Number.isFinite(initial)){pending={event_id:0,total:initial,previous_total:initial,new_badges:[]};completed=true;}
 function schedule(){cancelAnimationFrame(frame);frame=requestAnimationFrame(geometry);}
 document.addEventListener('lichtpunt:badge-attention',e=>animate(e.detail));
 if('ResizeObserver'in window)new ResizeObserver(schedule).observe(grid);
 window.addEventListener('resize',schedule,{passive:true});grid.addEventListener('toggle',schedule,true);document.fonts?.ready.then(schedule);schedule();
 if(grid.dataset.pendingAttention)try{animate(JSON.parse(grid.dataset.pendingAttention));}catch(e){}
})();
