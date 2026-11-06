(()=>{'use strict';
const form=document.querySelector('.story-write-form');
if(form){for(const name of ['title','body']){const field=form.elements.namedItem(name),counter=form.querySelector(`[data-story-count="${name}"]`);if(!field||!counter)continue;const update=()=>{counter.textContent=`${Array.from(field.value).length} / ${field.maxLength}`;};field.addEventListener('input',update);update();}}
const en=document.documentElement.lang==='en';
const examples=en?{
encounter:'Who surprised you with a kind gesture? Tell us what happened and why it stayed with you.',
achievement:'What did you achieve, however small? Tell us about the steps you took and what made you proud.',
together:'What became possible by helping each other? Tell us who joined in and what you created together.'
}:{
encounter:'Wie verraste jou met een lief gebaar? Vertel wat er gebeurde en waarom dat je bijbleef.',
achievement:'Wat lukte jou, hoe klein ook? Vertel over de stappen die je zette en waar je trots op bent.',
together:'Wat lukte er doordat jullie elkaar hielpen? Vertel wie er meededen en wat jullie samen maakten.'
};
const target=document.getElementById('story-inspiration-example');
if(target)for(const button of document.querySelectorAll('[data-story-prompt]')){button.setAttribute('aria-pressed','false');button.addEventListener('click',()=>{for(const other of document.querySelectorAll('[data-story-prompt]'))other.setAttribute('aria-pressed',String(other===button));target.textContent=examples[button.dataset.storyPrompt]||'';target.hidden=false;});}
})();
