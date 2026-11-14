(()=>{
 'use strict';
 document.querySelectorAll('.moment-write-form').forEach(form=>{
  const input=form.querySelector('textarea[name="body"]'), counter=form.querySelector('[data-moment-count]');
  if(input&&counter){const update=()=>counter.textContent=`${Array.from(input.value).length} / 500`;input.addEventListener('input',update);update();}
 });
 document.addEventListener('submit',event=>{const form=event.target.closest('[data-confirm-moment]');if(form&&!window.confirm(form.dataset.confirmMoment))event.preventDefault();});

 // Status messages, loaded images and open edit forms can all change a card's height.
 document.querySelectorAll('.moments-studio .moment-keepsake-grid').forEach(grid=>{
  let frame=0;
  const resize=()=>{
   frame=0;
   const cards=[...grid.querySelectorAll(':scope > .moment-keepsake')];
   if(cards.length<=2){grid.style.removeProperty('--moment-visible-height');grid.removeAttribute('tabindex');return;}
   const top=grid.getBoundingClientRect().top;
   const bottom=Math.max(...cards.slice(0,2).map(card=>card.getBoundingClientRect().bottom));
   const padding=parseFloat(getComputedStyle(grid).paddingBottom)||0;
   const height=Math.ceil(bottom-top+grid.scrollTop+padding);
   if(height>0){const value=height+'px';if(grid.style.getPropertyValue('--moment-visible-height')!==value)grid.style.setProperty('--moment-visible-height',value);}
   grid.setAttribute('tabindex','0');grid.setAttribute('role','region');grid.setAttribute('aria-label',document.documentElement.lang==='en'?'Your moments. Scroll for more.':'Jouw momentjes. Scroll voor meer.');
  };
  const schedule=()=>{if(!frame)frame=requestAnimationFrame(resize);};
  if(typeof ResizeObserver!=='undefined'){
   const observer=new ResizeObserver(schedule);observer.observe(grid);
   [...grid.children].slice(0,2).forEach(card=>observer.observe(card));
  }
  new MutationObserver(schedule).observe(grid,{childList:true,subtree:true,characterData:true});
  grid.addEventListener('load',schedule,true);grid.addEventListener('toggle',schedule,true);
  window.addEventListener('resize',schedule);document.fonts?.ready.then(schedule);schedule();
 });
})();
