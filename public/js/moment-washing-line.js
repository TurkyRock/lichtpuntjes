/* Native <details> remains the fallback; enhanced reading never changes note height. */
(()=>{
 'use strict';
 const dialog=document.getElementById('momentDetail');
 const body=document.getElementById('momentDetailBody');
 if(!dialog||!body||typeof dialog.showModal!=='function')return;
 let opener=null;
 document.addEventListener('click',event=>{
  const summary=event.target.closest('[data-moment-open]');
  if(!summary)return;
  const details=summary.closest('.moment-note-more');
  const content=details?.querySelector('.moment-full-content');
  if(!content)return;
  event.preventDefault();
  opener=summary;
  const clone=content.cloneNode(true);
  body.replaceChildren(clone);
  if(!dialog.open)dialog.showModal();
 });
 dialog.querySelector('[data-close-moment]')?.addEventListener('click',()=>dialog.close());
 dialog.addEventListener('click',event=>{
  if(event.target!==dialog)return;
  const rect=dialog.getBoundingClientRect();
  if(event.clientX<rect.left||event.clientX>rect.right||event.clientY<rect.top||event.clientY>rect.bottom)dialog.close();
 });
 dialog.addEventListener('close',()=>{
  body.replaceChildren();
  if(opener?.isConnected)opener.focus();
  opener=null;
 });
})();
