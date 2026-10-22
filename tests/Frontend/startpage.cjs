const fs=require('fs'),vm=require('vm'),assert=require('assert'),path=require('path');
const source=fs.readFileSync(path.resolve(__dirname,'../../resources/views/lichtpunt.blade.php'),'utf8');
const init=source.slice(source.indexOf("const startSeenKey="),source.indexOf('function renderStart()'));
const show=source.slice(source.indexOf('function showStart('),source.indexOf("$('openStart').addEventListener"));
const timing=source.slice(source.indexOf('setTimeout(()=>{startVisitEligible=true;showStart();}'),source.indexOf('function communityMarkup('));
const storage=new Map();let now=1800000000000;
function visit(blocked=false){const nodes={momentDetail:{open:false,addEventListener(){}},reader:{open:false,addEventListener(){}},siteShareDialog:{open:false,addEventListener(){}},startDialog:{open:false,showModal(){this.open=true;}}};const timers=[];const ctx={setTimeout(fn,delay){timers.push({fn,delay});},Date:{now:()=>now},Number,JSON,document:{addEventListener(){}},localStorage:{getItem(k){if(blocked)throw Error('blocked');return storage.get(k)||null;},setItem(k,v){if(blocked)throw Error('blocked');storage.set(k,v);}},$:id=>nodes[id]};vm.createContext(ctx);vm.runInContext(init+show,ctx);return{nodes,timers,run:s=>vm.runInContext(s,ctx)};}
let v=visit();v.run('showStart()');assert(!v.nodes.startDialog.open);
now+=10000;v=visit();v.run('showStart()');assert(!v.nodes.startDialog.open); // reload
now+=31*60000;v=visit();v.nodes.reader.open=true;v.run('showStart()');assert(!v.nodes.startDialog.open);
v.nodes.reader.open=false;v.nodes.momentDetail.open=true;v.run('showStart()');assert(!v.nodes.startDialog.open);v.nodes.momentDetail.open=false;v.run('showStart()');assert(v.nodes.startDialog.open); // second visit
v.nodes.startDialog.open=false;v.run('showStart()');assert(!v.nodes.startDialog.open);
now+=31*60000;v=visit();v.run('showStart()');assert(!v.nodes.startDialog.open); // shown once
v.run('showStart(true)');assert(v.nodes.startDialog.open); // manual still works
storage.clear();v=visit();v.run('showStart(true)');assert(v.nodes.startDialog.open);
now+=31*60000;v=visit();v.run('showStart()');assert(!v.nodes.startDialog.open); // manual counts as seen
storage.clear();v=visit();now+=20*60000;v.run('keepStartVisitActive()');now+=20*60000;v=visit();v.run('showStart()');assert(!v.nodes.startDialog.open); // activity extends visit
storage.set('lichtpunt-start-visits-v1','broken');v=visit();v.run('showStart()');assert(!v.nodes.startDialog.open);
v=visit(true);v.run('showStart()');assert(!v.nodes.startDialog.open);v.run('showStart(true)');assert(v.nodes.startDialog.open);
storage.clear();v=visit();v.run(timing);assert.equal(v.timers[0].delay,300000);assert(!v.nodes.startDialog.open);v.timers[0].fn();assert(v.nodes.startDialog.open);
storage.clear();v=visit();now+=31*60000;v=visit();v.run(timing);assert.equal(v.timers[0].delay,0);v.timers[0].fn();assert(v.nodes.startDialog.open);
storage.clear();v=visit();v.run(timing);v.nodes.siteShareDialog.open=true;v.timers[0].fn();assert(!v.nodes.startDialog.open);v.nodes.siteShareDialog.open=false;v.run('showPendingStart()');v.timers.at(-1).fn();assert(v.nodes.startDialog.open);
console.log('Startpagina: eerste bezoek, herladen, tweede bezoek, dialoogblokkade, eenmalig tonen, handmatig openen, activiteit en geblokkeerde opslag geslaagd.');
