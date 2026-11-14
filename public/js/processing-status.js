(() => {
 const cards=[...document.querySelectorAll('[data-processing-card]')];
 if(!cards.length)return;
 let running=false;
 async function refresh(){
  if(running||document.hidden)return;running=true;
  try{for(const card of cards){
   if(!card.isConnected)continue;
   try{
    const response=await fetch(card.dataset.progressUrl,{credentials:'same-origin',headers:{Accept:'application/json'},cache:'no-store',signal:AbortSignal.timeout(8000)});
    if(!response.ok)continue;
    const data=await response.json();if(typeof data.heading!=='string'||typeof data.message!=='string')continue;
    const changed=card.dataset.progressCode!==data.code;card.dataset.progressCode=data.code;
    card.querySelector('[data-progress-heading]').textContent=data.heading;
    card.querySelector('[data-progress-message]').textContent=data.message;
    const position=card.querySelector('[data-progress-position]');position.hidden=!data.position;position.textContent=data.position?card.dataset.progressPlace.replace('{n}',String(data.position)):'';
    card.querySelectorAll('.processing-steps li').forEach((li,i)=>{li.classList.toggle('is-done',i<data.stage);if(i+1===data.stage)li.setAttribute('aria-current','step');else li.removeAttribute('aria-current');});
    const link=card.querySelector('[data-progress-result]');const safe=typeof data.link==='string'&&data.link.startsWith('/?story=');link.hidden=!safe;if(safe)link.setAttribute('href',data.link);
    const article=card.closest('.moment-keepsake,.story-keepsake');
    const momentImage=typeof data.image_url==='string'&&/^\/momentjes\/\d+\/afbeelding\?v=\d+$/.test(data.image_url);
    const storyImage=typeof data.image_url==='string'&&/^\/news-images\/[a-f0-9]{64}\.(jpg|png)$/.test(data.image_url);
    if(article&&(momentImage||storyImage)&&!article.querySelector('.moment-small-image,.story-polaroid')){
     const figure=document.createElement('figure');figure.className=momentImage?'moment-small-image moment-small-image--account '+(data.image_style==='sketch'?'is-sketch':'is-photo'):'story-polaroid';
     const image=document.createElement('img');image.src=data.image_url;image.alt=card.dataset.progressImagealt;image.width=momentImage?384:96;image.height=momentImage?384:108;image.loading='lazy';
     if(momentImage){const window=document.createElement('span');window.className='moment-image-window';window.append(image);figure.append(window);article.querySelector('.moment-keepsake-text').before(figure);}else{figure.append(image);article.prepend(figure);}
    }
    if(changed)card.querySelector('[data-progress-announce]').textContent=data.heading;
   }catch{/* Keep the last saved status and every unsent form field. */}
  }}finally{running=false;}
 }
 setInterval(refresh,10000);
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});
})();
