const fs=require('fs'),path=require('path'),vm=require('vm'),assert=require('assert');
const script=fs.readFileSync(path.resolve(__dirname,'../../public/js/analytics.js'),'utf8');
function setup({allowed=false,expires=Date.now()+60000,pathname='/',id='G-3JSRH397WV'}={}) {
 const handlers={},scripts=[],intervals=[];let reloads=0;
 const ctx={URL,Date,Number,String,encodeURIComponent,window:{lichtpuntAnalyticsConfig:{id,allowed,expires,nonce:'nonce123'},location:{pathname,origin:'https://lichtpunt.example',protocol:'https:',search:'?email=secret@example.com',reload(){reloads++;}}},
 document:{referrer:'https://search.example/results?email=secret@example.com#token',documentElement:{lang:'nl'},head:{appendChild(s){scripts.push(s);}},createElement(){return{};},addEventListener(name,fn){handlers[name]=fn;}},setInterval(fn){intervals.push(fn);}};
 vm.createContext(ctx);vm.runInContext(script,ctx);
 return{ctx,scripts,intervals,event:(name,detail)=>handlers[name]?.({detail}),reloads:()=>reloads,calls:()=>Array.from(ctx.window.dataLayer||[],x=>Array.from(x))};
}
let s=setup();assert.equal(s.scripts.length,0);assert.equal(s.calls().length,0);
s.event('lichtpunt:statistics-consent',{allowed:false});assert.equal(s.scripts.length,0);
s.event('lichtpunt:statistics-consent',{allowed:true});assert.equal(s.scripts.length,1);assert.equal(s.scripts[0].nonce,'nonce123');assert(s.scripts[0].src.includes('G-3JSRH397WV'));
const consent=s.calls().find(c=>c[0]==='consent');assert.equal(consent[2].analytics_storage,'granted');assert.equal(consent[2].ad_user_data,'denied');
const config=s.calls().find(c=>c[0]==='config')[2];assert.equal(config.send_page_view,false);assert.equal(config.cookie_domain,'none');assert.equal(config.cookie_expires,15552000);assert.equal(config.allow_google_signals,false);
assert.equal(config.page_location,'https://lichtpunt.example/');assert.equal(config.page_referrer,'https://search.example/');
s.event('lichtpunt:statistics-consent',{allowed:true});assert.equal(s.scripts.length,1);assert.equal(s.calls().filter(c=>c[1]==='page_view').length,1);
s.event('lichtpunt:article-open',{id:'61',category:'community',name:'Private name',body:'Private story'});
let event=s.calls().at(-1);assert.equal(event[1],'article_view');assert.equal(event[2].article_id,'61');assert(!('name' in event[2]));assert(!('body' in event[2]));assert(!JSON.stringify(s.calls()).includes('secret@example.com'));
const count=s.calls().length;s.event('lichtpunt:article-open',{id:'bad',category:'sport'});assert.equal(s.calls().length,count);
s.event('lichtpunt:statistics-consent',{allowed:false});assert.equal(s.ctx.window['ga-disable-G-3JSRH397WV'],true);assert.equal(s.reloads(),1);
s.event('lichtpunt:article-open',{id:'61',category:'sport'});assert.equal(s.calls().length,count);
s=setup({allowed:true});assert.equal(s.scripts.length,1);
s=setup({allowed:true,expires:Date.now()-1});assert.equal(s.scripts.length,0);
s=setup({allowed:true,pathname:'/profiel'});assert.equal(s.scripts.length,0);
s=setup({allowed:true,id:'bad'});assert.equal(s.scripts.length,0);
console.log('Analytics: geen Google-verzoek zonder toestemming, één tag, intrekken, verlopen keuze, privépagina’s en veilige meetvelden geslaagd.');
