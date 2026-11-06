(()=>{
 'use strict';
 document.querySelectorAll('.moment-write-form').forEach(form=>{
  const input=form.querySelector('textarea[name="body"]'), counter=form.querySelector('[data-moment-count]');
  if(input&&counter){const update=()=>counter.textContent=`${Array.from(input.value).length} / 500`;input.addEventListener('input',update);update();}
 });
 document.addEventListener('submit',event=>{const form=event.target.closest('[data-confirm-moment]');if(form&&!window.confirm(form.dataset.confirmMoment))event.preventDefault();});
})();
