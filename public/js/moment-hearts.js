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

/* Enlarge small central sketches without changing stored image files. */
(() => {
 'use strict';
 function focusImage(image) {
  if (!image.naturalWidth || !image.naturalHeight) return;
  const figure=image.closest('.moment-small-image.is-sketch');
  if (!figure) return;
  try {
   const canvas=document.createElement('canvas');canvas.width=96;canvas.height=96;
   const context=canvas.getContext('2d',{willReadFrequently:true});if(!context)return;
   context.drawImage(image,0,0,96,96);
   const pixels=context.getImageData(0,0,96,96).data;
   const corners=[0,95,95*96,96*96-1].map(p=>Array.from(pixels.slice(p*4,p*4+3)));
   const background=[0,1,2].map(c=>corners.reduce((sum,p)=>sum+p[c],0)/4);
   // Photo-like or strongly varying backgrounds retain their complete image.
   if(Math.min(...background)<205||corners.some(p=>p.some((v,c)=>Math.abs(v-background[c])>25)))return;
   let left=96,top=96,right=-1,bottom=-1,count=0;
   for(let y=0;y<96;y++)for(let x=0;x<96;x++){
    const i=(y*96+x)*4;
    if(pixels[i+3]<128)continue;
    const difference=Math.max(...background.map((v,c)=>v-pixels[i+c]));
    if(difference<55)continue;
    left=Math.min(left,x);right=Math.max(right,x);top=Math.min(top,y);bottom=Math.max(bottom,y);count++;
   }
   if(count<12||left<4||top<4||right>91||bottom>91)return;
   const extent=Math.max(right-left+1,bottom-top+1)/96;
   const scale=Math.min(3.2,1/(extent*1.35));if(scale<1.15)return;
   const cx=(left+right+1)/192,cy=(top+bottom+1)/192;
   figure.style.setProperty('--moment-image-scale',scale.toFixed(3));
   figure.style.setProperty('--moment-image-x',((.5-cx*scale)*100).toFixed(2)+'%');
   figure.style.setProperty('--moment-image-y',((.5-cy*scale)*100).toFixed(2)+'%');
  } catch { /* Keep the complete source if canvas access is unavailable. */ }
 }
 for(const image of document.querySelectorAll('.moment-small-image.is-sketch img')){
  image.addEventListener('load',()=>focusImage(image),{once:true});
  if(image.complete)focusImage(image);
 }
})();
