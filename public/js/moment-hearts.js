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
