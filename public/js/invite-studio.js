(() => {
 'use strict';
 const root=document.querySelector('[data-invite-studio]');if(!root)return;
 const en=root.dataset.language==='en',url=root.dataset.inviteUrl,message=root.querySelector('#inviteMessage'),linkInput=root.querySelector('#inviteLink'),status=root.querySelector('#inviteStatus'),dialog=root.querySelector('[data-invite-dialog]'),dialogStatus=root.querySelector('[data-invite-dialog-status]'),manual=root.querySelector('[data-invite-manual-copy]');
 const text=()=>message.value.trim(),full=()=>text()+(text()?'\n\n':'')+url;
 const label=(nl,eng)=>en?eng:nl;
 const notify=value=>{status.textContent=value;if(dialog.open)dialogStatus.textContent=value;};
 const platforms=Object.freeze({
  instagram:{name:'Instagram',url:'https://www.instagram.com/',card:true},
  tiktok:{name:'TikTok',url:'https://www.tiktok.com/',card:true},
  messenger:{name:'Messenger',url:'https://www.messenger.com/',card:false},
  signal:{name:'Signal',url:'https://signal.org/',card:false}
 });
 let trigger=null,downloading=false,nativePending=false;
 function showHelp(key,source){
  const p=platforms[key];trigger=source||document.activeElement;
  root.querySelector('#inviteDialogTitle').textContent=p?label('Deel via ','Share via ')+p.name:label('Geef je lichtpuntje door','Share a little sunshine');
  root.querySelector('[data-invite-instructions]').textContent=p?(p.card?label('Kopieer je bericht met de link en download het kaartje. Open daarna '+p.name+' en kies zelf je story of bericht. Voeg je uitnodigingslink toe waar de app dat toestaat.','Copy your message and link, then download the card. Open '+p.name+' and choose a story or post. Add your invitation link where the app allows it.'):label('Kopieer je bericht met de link. Open '+p.name+' of kies de app in het deelmenu van je telefoon en plak het bericht in een gesprek.','Copy your message and link. Open '+p.name+' or choose the app in your phone’s sharing menu, then paste the message into a conversation.')):label('Je kunt je bericht en link kopiëren, of het kaartje downloaden en zelf delen.','You can copy your message and link, or download the card and share it yourself.');
  const open=root.querySelector('[data-invite-open]');open.hidden=!p||key==='signal';if(p){open.href=p.url;open.textContent=label('Open ','Open ')+p.name;}
  dialog.querySelector('[data-invite-native]').hidden=typeof navigator.share!=='function';
  dialogStatus.textContent='';manual.hidden=true;
  if(!dialog.open)dialog.showModal();
 }
 function closeHelp(){dialog.close();if(trigger?.isConnected)trigger.focus();}
 async function copy(kind,source){
  const value=kind==='link'?url:full();
  try{
   if(!navigator.clipboard?.writeText)throw new Error('unavailable');
   await navigator.clipboard.writeText(value);
   notify(kind==='link'?label('Link gekopieerd. Maak er iemand blij mee!','Link copied. Make someone’s day!'):label('Bericht en link gekopieerd. Klaar om te plakken!','Message and link copied. Ready to paste!'));
  }catch{
   if(!dialog.open)showHelp(null,source);
   manual.value=value;manual.hidden=false;manual.focus();manual.select();manual.setSelectionRange(0,value.length);
   notify(label('Selecteer en kopieer de tekst: houd ingedrukt op je telefoon, of gebruik Ctrl+C / ⌘C.','Select and copy the text: press and hold on your phone, or use Ctrl+C / ⌘C.'));
  }
 }
 function updateLinks(){
  const encoded=encodeURIComponent,body=full();
  const urls={
   whatsapp:'https://wa.me/?text='+encoded(body),
   facebook:'https://www.facebook.com/sharer/sharer.php?u='+encoded(url),
   linkedin:'https://www.linkedin.com/sharing/share-offsite/?url='+encoded(url),
   threads:'https://www.threads.com/intent/post?text='+encoded(body),
   x:'https://twitter.com/intent/tweet?text='+encoded(text())+'&url='+encoded(url),
   pinterest:'https://www.pinterest.com/pin/create/button/?url='+encoded(url)+'&media='+encoded(new URL('/images/invite-sun-envelope-v1.png',location.origin).href)+'&description='+encoded(text()),
   telegram:'https://t.me/share/url?url='+encoded(url)+'&text='+encoded(text()),
   email:'mailto:?subject='+encoded(label('Een lichtpuntje voor jou','A little sunshine for you'))+'&body='+encoded(body)
  };
  root.querySelectorAll('[data-invite-platform]').forEach(a=>{const next=urls[a.dataset.invitePlatform];if(next)a.href=next;});
 }
 async function shareNative(source){
  if(nativePending)return;
  if(typeof navigator.share!=='function'){showHelp(null,source);return;}
  nativePending=true;source.disabled=true;
  try{await navigator.share({title:'Jouw Lichtpuntje',text:text(),url});notify(label('Je bent terug uit het deelmenu. Dank je wel voor het doorgeven!','You are back from the sharing menu. Thanks for spreading a little sunshine!'));}
  catch(error){if(error.name==='AbortError')notify(label('Delen geannuleerd. Je kunt het later nog eens proberen.','Sharing cancelled. You can try again later.'));else showHelp(null,source);}
  finally{nativePending=false;source.disabled=false;}
 }
 function wrap(ctx,value,x,y,width,lineHeight){
  let line='';for(const char of Array.from(value)){if(ctx.measureText(line+char).width>width&&line){ctx.fillText(line.trim(),x,y);y+=lineHeight;line=char;}else line+=char;}if(line)ctx.fillText(line.trim(),x,y);return y+lineHeight;
 }
 async function cardBlob(){
  const canvas=document.createElement('canvas');canvas.width=1080;canvas.height=1920;const ctx=canvas.getContext('2d');if(!ctx)throw new Error('canvas unavailable');
  const art=new Image();art.src='/images/invite-sun-envelope-v1.png';await art.decode();
  ctx.fillStyle='#f7f2e5';ctx.fillRect(0,0,1080,1920);
  ctx.fillStyle='#dfe8d3';ctx.fillRect(52,52,976,1816);ctx.fillStyle='#fff9ec';ctx.fillRect(83,82,914,1756);
  // Small deterministic paper flecks; no personal data or external service involved.
  ctx.fillStyle='#71634c0a';for(let i=0;i<6500;i++)ctx.fillRect((i*149)%1080,(i*283)%1920,1+(i%2),1);
  ctx.textAlign='center';ctx.fillStyle='#25493b';ctx.font='46px Georgia,serif';ctx.fillText('Jouw Lichtpuntje',540,180);
  ctx.fillStyle='#e6c854';ctx.fillRect(330,207,420,9);
  ctx.fillStyle='#25493b';ctx.font='74px Georgia,serif';const title=en?['A little good news','does you good']:['Een beetje','goed nieuws','doet goed'];title.forEach((line,i)=>ctx.fillText(line,540,330+i*91));
  ctx.font='35px system-ui,sans-serif';ctx.fillStyle='#4e6654';
  const caption=en?['Positive news, little joys','and real stories.','Come and take a look.']:['Positief nieuws, kleine gelukjes','en echte verhalen.','Kom je ook kijken?'];caption.forEach((line,i)=>ctx.fillText(line,540,680+i*52));
  ctx.drawImage(art,180,850,720,720);
  ctx.font='32px system-ui,sans-serif';ctx.fillStyle='#25493b';ctx.fillText(label('Een lichtpuntje voor jou ♡','A little sunshine for you ♡'),540,1590);
  ctx.font='23px system-ui,sans-serif';wrap(ctx,url,540,1660,850,34);
  ctx.font='24px system-ui,sans-serif';ctx.fillStyle='#596b57';ctx.fillText(label('Gratis. Zonder reclame. Met een glimlach.','Free. No ads. With a smile.'),540,1790);
  return new Promise((resolve,reject)=>canvas.toBlob(blob=>blob?resolve(blob):reject(new Error('export unavailable')),'image/png'));
 }
 async function download(source){
  if(downloading)return;downloading=true;source.disabled=true;
  try{const blob=await cardBlob(),blobUrl=URL.createObjectURL(blob),a=document.createElement('a');a.href=blobUrl;a.download='jouw-lichtpuntje-'+(en?'share-card':'deelkaartje')+'.png';document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(blobUrl),30000);notify(label('Je deelkaartje is klaar. Voeg bij het delen ook je uitnodigingslink toe.','Your share card is ready. Add your invitation link when sharing.'));}
  catch{notify(label('Het kaartje kon niet worden gemaakt. Je kunt je bericht en link wel kopiëren.','The card could not be created. You can still copy your message and link.'));}
  finally{downloading=false;source.disabled=false;}
 }
 root.addEventListener('click',event=>{
  const copyButton=event.target.closest('[data-invite-copy]');if(copyButton){copy(copyButton.dataset.inviteCopy,copyButton);return;}
  const help=event.target.closest('[data-invite-help]');if(help){showHelp(help.dataset.inviteHelp,help);return;}
  const native=event.target.closest('[data-invite-native]');if(native){shareNative(native);return;}
  const dl=event.target.closest('[data-invite-download]');if(dl){download(dl);return;}
  if(event.target.closest('[data-invite-close]'))closeHelp();
 });
 dialog.addEventListener('cancel',event=>{event.preventDefault();closeHelp();});
 dialog.addEventListener('click',event=>{if(event.target!==dialog)return;const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)closeHelp();});
 message.addEventListener('input',updateLinks);linkInput.addEventListener('focus',()=>linkInput.select());updateLinks();
})();
