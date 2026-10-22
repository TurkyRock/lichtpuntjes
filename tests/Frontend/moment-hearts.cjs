const fs=require('fs'),vm=require('vm'),assert=require('assert');
const code=fs.readFileSync('public/js/moment-hearts.js','utf8');
let submit;const events=[],requests=[];
function make(){const button={disabled:false,textContent:'♡ 0',setAttribute(k,v){this[k]=v}},liked={value:'1'},status={textContent:''};return {dataset:{momentId:'7'},action:'https://example.test/momentjes/7/hart',button,liked,status,setAttribute(){},querySelector(s){return s==='button'?button:s==='[name="liked"]'?liked:s==='[name="_token"]'?{value:'csrf'}:status;}}}
const a=make(),b=make(),templateForm=make();const doc={documentElement:{lang:'nl'},addEventListener(t,cb){if(t==='submit')submit=cb},dispatchEvent(e){events.push(e)},querySelectorAll(s){return s==='template'?[{content:{querySelectorAll:()=>[templateForm]}}]:[a,b]}};
vm.runInNewContext(code,{document:doc,Set,Array,Number,String,CustomEvent:function(type){this.type=type},fetch:(url,opts)=>new Promise(resolve=>requests.push({url,opts,resolve}))});
const send=f=>submit({target:{closest:()=>f},preventDefault(){}});
(async()=>{
 const first=send(a);assert(a.button.disabled&&b.button.disabled&&templateForm.button.disabled);send(b);assert.equal(requests.length,1);assert.equal(JSON.parse(requests[0].opts.body)._token,'csrf');
 requests[0].resolve({ok:true,json:async()=>({moment_id:'7',liked:true,hearts_count:3})});await first;
 for(const f of [a,b,templateForm]){assert.equal(f.button.textContent,'♥ 3');assert.equal(f.button['aria-pressed'],'true');assert.equal(f.liked.value,'0');assert.equal(f.button.disabled,false)}
 assert.equal(events[0].type,'lichtpunt:points-check');
 const failed=send(b);requests[1].resolve({ok:false});await failed;assert.equal(a.button.textContent,'♥ 3');assert(a.status.textContent.includes('Probeer opnieuw'));assert(!b.button.disabled);
 console.log('Momenthartjes: alle kopieën en templates, dubbelklik, CSRF, foutbehoud en puntenmelding geslaagd.');
})().catch(e=>{console.error(e);process.exitCode=1});
