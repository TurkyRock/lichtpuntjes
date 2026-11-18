(()=>{'use strict';
const form=document.querySelector('.story-write-form');
if(form){for(const name of ['title','body']){const field=form.elements.namedItem(name),counter=form.querySelector(`[data-story-count="${name}"]`);if(!field||!counter)continue;const update=()=>{counter.textContent=`${Array.from(field.value).length} / ${field.maxLength}`;};field.addEventListener('input',update);update();}}
const sensitive=form?.querySelector('#story-sensitive'),consent=form?.querySelector('[name="sensitive_consent"]');
if(sensitive&&consent){const sync=()=>{consent.required=sensitive.checked;consent.disabled=!sensitive.checked;if(!sensitive.checked)consent.checked=false;};sensitive.addEventListener('change',sync);sync();}
const en=document.documentElement.lang==='en';
const examples=en?{
encounter:'Tell us about a kind gesture that stayed with you.',
achievement:'What did you achieve, however small? Tell us about the steps you took and what made you proud.',
together:'What became possible by helping each other? Tell us who joined in and what you created together.'
}:{
encounter:'Vertel over een lief gebaar dat je bijbleef.',
achievement:'Wat lukte jou, hoe klein ook? Vertel over de stappen die je zette en waar je trots op bent.',
together:'Wat lukte er doordat jullie elkaar hielpen? Vertel wie er meededen en wat jullie samen maakten.'
};
const target=document.getElementById('story-inspiration-example');
if(target)for(const button of document.querySelectorAll('[data-story-prompt]')){button.setAttribute('aria-pressed','false');button.addEventListener('click',()=>{for(const other of document.querySelectorAll('[data-story-prompt]'))other.setAttribute('aria-pressed',String(other===button));target.textContent=examples[button.dataset.storyPrompt]||'';target.hidden=false;});}
})();
