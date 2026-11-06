(()=>{
 'use strict';
 const dialog=document.getElementById('supportThanksDialog');
 if(!dialog||typeof dialog.showModal!=='function')return;
 dialog.querySelector('[data-close-support-thanks]')?.addEventListener('click',()=>dialog.close());
 dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dialog.close();});
 dialog.showModal();
})();
