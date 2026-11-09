(() => {
 'use strict';
 const pending = new Set();
 const forms = () => [...document.querySelectorAll('form[data-moment-heart]'), ...Array.from(document.querySelectorAll('template')).flatMap(t => Array.from(t.content.querySelectorAll('form[data-moment-heart]')))];
 const sameForms = id => forms().filter(f => f.dataset.momentId === id);
 const busy = (id, value) => sameForms(id).forEach(f => { f.querySelector('button').disabled = value; f.setAttribute('aria-busy', String(value)); });
 document.addEventListener('submit', async event => {
  const form = event.target.closest('form[data-moment-heart]'); if (!form) return;
  event.preventDefault(); const id = form.dataset.momentId; if (!id || pending.has(id)) return;
  pending.add(id); busy(id, true);
  sameForms(id).forEach(f => { f.querySelector('[role="status"]').textContent = ''; });
  try {
   const response = await fetch(form.action, {method:'POST', credentials:'same-origin', headers:{Accept:'application/json','Content-Type':'application/json'}, body:JSON.stringify({_token:form.querySelector('[name="_token"]').value, liked:form.querySelector('[name="liked"]').value === '1'})});
   if (!response.ok) throw new Error('Save failed');
   const result = await response.json();
   if (String(result.moment_id) !== id || typeof result.liked !== 'boolean' || !Number.isInteger(result.hearts_count) || result.hearts_count < 0) throw new Error('Invalid response');
   sameForms(id).forEach(f => {
    const b = f.querySelector('button'), en = document.documentElement.lang === 'en';
    b.textContent = (result.liked ? '♥' : '♡') + ' ' + result.hearts_count;
    b.setAttribute('aria-pressed', String(result.liked));
    b.setAttribute('aria-label', en ? (result.liked ? 'Remove heart' : 'Give a heart') : (result.liked ? 'Hartje intrekken' : 'Geef een hartje'));
    f.querySelector('[name="liked"]').value = result.liked ? '0' : '1';
   });
   document.dispatchEvent(new CustomEvent('lichtpunt:points-check', {detail:{}}));
  } catch (error) {
   sameForms(id).forEach(f => { f.querySelector('[role="status"]').textContent = document.documentElement.lang === 'en' ? 'Could not save your heart. Please try again.' : 'Je hartje kon niet worden opgeslagen. Probeer opnieuw.'; });
  } finally {pending.delete(id); busy(id, false);}
 });
})();

/* Focus central drawings in every frame, including newly opened reader dialogs. */
(() => {
 'use strict';
 const bound=new WeakSet(),bounds=new Map();
 function subjectBounds(image) {
  const source=image.currentSrc||image.src;if(bounds.has(source))return bounds.get(source);
  const canvas=document.createElement('canvas');canvas.width=96;canvas.height=96;
  const context=canvas.getContext('2d',{willReadFrequently:true});if(!context)return null;
  context.drawImage(image,0,0,96,96);
  const pixels=context.getImageData(0,0,96,96).data;
  const corners=[0,95,95*96,96*96-1].map(p=>Array.from(pixels.slice(p*4,p*4+3)));
  const background=[0,1,2].map(c=>corners.reduce((sum,p)=>sum+p[c],0)/4);
  if(Math.min(...background)<205||corners.some(p=>p.some((v,c)=>Math.abs(v-background[c])>25))){bounds.set(source,null);return null;}
  let left=96,top=96,right=-1,bottom=-1,count=0;
  for(let y=0;y<96;y++)for(let x=0;x<96;x++){
   const i=(y*96+x)*4;if(pixels[i+3]<128)continue;
   if(Math.max(...background.map((v,c)=>v-pixels[i+c]))<55)continue;
   left=Math.min(left,x);right=Math.max(right,x);top=Math.min(top,y);bottom=Math.max(bottom,y);count++;
  }
  const result=count>=12&&left>=4&&top>=4&&right<=91&&bottom<=91?{left:left/96,top:top/96,right:(right+1)/96,bottom:(bottom+1)/96}:null;
  bounds.set(source,result);return result;
 }
 function focusImage(image) {
  if(!image.naturalWidth||!image.naturalHeight||!image.closest('.moment-small-image.is-sketch'))return;
  const frame=image.closest('.moment-image-window');if(!frame)return;
  const rect=frame.getBoundingClientRect();if(!rect.width||!rect.height)return;
  try {
   const subject=subjectBounds(image);if(!subject)return;
   // Fit the complete drawing, plus a narrow safety margin, to this frame's ratio.
   const ratio=rect.width/rect.height;
   let width=Math.max((subject.right-subject.left)*1.12,(subject.bottom-subject.top)*1.12*ratio,1/5);
   let height=width/ratio;
   if(width>1||height>1)return;
   const x=Math.max(0,Math.min(1-width,(subject.left+subject.right-width)/2));
   const y=Math.max(0,Math.min(1-height,(subject.top+subject.bottom-height)/2));
   image.style.width=(100/width).toFixed(3)+'%';image.style.height=(100/height).toFixed(3)+'%';
   image.style.left=(-x/width*100).toFixed(3)+'%';image.style.top=(-y/height*100).toFixed(3)+'%';
  } catch { /* The original image still fills the frame when focus is unavailable. */ }
 }
 const resize=typeof ResizeObserver==='function'?new ResizeObserver(entries=>{for(const entry of entries){const image=entry.target.querySelector('img');if(image)focusImage(image);}}):null;
 function bind(image){
  if(bound.has(image))return;bound.add(image);
  image.addEventListener('load',()=>focusImage(image));
  const frame=image.closest('.moment-image-window');if(frame)resize?.observe(frame);
  if(image.complete)focusImage(image);
 }
 function scan(root){
  if(root.nodeType===1&&root.matches('.moment-small-image.is-sketch img'))bind(root);
  root.querySelectorAll?.('.moment-small-image.is-sketch img').forEach(bind);
 }
 scan(document);
 if(typeof MutationObserver==='function')new MutationObserver(records=>{for(const record of records)for(const node of record.addedNodes)scan(node);}).observe(document.body,{childList:true,subtree:true});
})();

/* A missing portrait falls back to the author's initial in the same stamp. */
(() => {
 'use strict';
 const hideMissing=image=>{image.hidden=true;};
 document.querySelectorAll('img[data-moment-avatar]').forEach(image=>{
  image.addEventListener('error',()=>hideMissing(image));
  if(image.complete&&!image.naturalWidth)hideMissing(image);
 });
})();
