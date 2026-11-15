(()=>{
 const menu=document.querySelector('.account-menu-grouped');
 if(!menu)return;
 const toggle=menu.querySelector('.account-menu-toggle');
 const nav=menu.querySelector('#accountGroupedNav');
 if(!toggle||!nav)return;
 const mobile=window.matchMedia('(max-width:760px)');
 const expanded=value=>{nav.hidden=!value;toggle.setAttribute('aria-expanded',String(value));};
 const resize=()=>{toggle.hidden=!mobile.matches;if(mobile.matches&&nav.contains(document.activeElement))toggle.focus();else if(!mobile.matches&&document.activeElement===toggle)nav.querySelector('[aria-current=page]')?.focus();expanded(!mobile.matches);};
 toggle.addEventListener('click',()=>expanded(toggle.getAttribute('aria-expanded')!=='true'));
 menu.addEventListener('keydown',event=>{if(event.key==='Escape'&&mobile.matches&&!nav.hidden){expanded(false);toggle.focus();}});
 mobile.addEventListener('change',resize);
 resize();
})();
