<?php
namespace App\Http\Controllers;
use App\Models\{LightspotSubmission,User};
use App\Jobs\ProcessLightspot;
use App\Services\LightspotProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache,DB};
class LightspotController extends Controller {
 private function author(Request $r):void {abort_unless($r->user()->community_accepted_at,403,'Bevestig eerst de huisregels in je account.');}
 private function fields(Request $r):array {
  $v=$r->validate(['title'=>'required|string|min:5|max:140','body'=>'required|string|min:120|max:6000','language'=>'required|in:nl,en','intentions'=>'accepted','website'=>'prohibited']);
  $v['category']='community';$v['title']=trim($v['title']);$v['body']=trim($v['body']);
  if(mb_strlen($v['title'])<5||mb_strlen($v['body'])<120)throw \Illuminate\Validation\ValidationException::withMessages(['body'=>'Schrijf minstens 120 tekens en een duidelijke titel.']);
  if(preg_match('~https?://|www\.|[\w.+-]+@[\w.-]+\.[a-z]{2,}~iu',$v['title'].' '.$v['body']))throw \Illuminate\Validation\ValidationException::withMessages(['body'=>'Laat links, e-mailadressen en contactgegevens weg.']);
  return array_intersect_key($v,array_flip(['title','body','language','category']));
 }
 public function create(Request $r){return redirect('/mijn-lichtpuntjes?lang='.app()->getLocale().'#new-story');}
 public function index(Request $r){$r->validate(['needs_attention'=>'sometimes|boolean']);$needsAttention=$r->boolean('needs_attention');return view('community.lightspot-list',['needsAttention'=>$needsAttention,'submissions'=>LightspotSubmission::where('user_id',$r->user()->id)->when($needsAttention,fn($q)=>$q->where('status','rejected'))->with('article')->latest()->paginate(6)->withQueryString()]);}
 public function store(Request $r){
  $this->author($r);$v=$this->fields($r);
  $s=DB::transaction(function()use($r,$v){$user=User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();abort_if($user->is_suspended,403);
   abort_if(LightspotSubmission::where('user_id',$user->id)->where('created_at','>=',now()->subDay())->count()>=3,429,'Maximaal drie verhalen per 24 uur. Morgen is er weer ruimte.');
   abort_if(LightspotSubmission::where('user_id',$user->id)->where('body',$v['body'])->exists(),422,'Je hebt dit verhaal al gedeeld.');
   return LightspotSubmission::create($v+['user_id'=>$user->id,'status'=>'pending']);
  });ProcessLightspot::dispatch($s->id);
  return redirect('/mijn-lichtpuntjes')->with('status',app()->getLocale()==='en'?'Your story is saved. We review it first, then create an illustration.':'Je verhaal is opgeslagen. Eerst keuren we het, daarna maken we de illustratie.');
 }
 public function edit(Request $r,LightspotSubmission $submission){abort_unless($submission->user_id===$r->user()->id,403);abort_unless($submission->status==='rejected',422);return view('community.lightspot-write',['submission'=>$submission]);}
 public function update(Request $r,LightspotSubmission $submission){
  $this->author($r);abort_unless($submission->user_id===$r->user()->id,403);$v=$this->fields($r);
  $lock=Cache::lock('lichtpunt-submission-'.$submission->id,2100);abort_unless($lock->get(),409,'Dit verhaal wordt verwerkt.');
  try{DB::transaction(function()use($r,$submission,$v){
   $submission->refresh();abort_unless($submission->status==='rejected',422);abort_if($submission->title===$v['title']&&$submission->body===$v['body'],422,'Pas eerst je verhaal aan.');
   abort_if(DB::table('lightspot_reviews')->where('submission_id',$submission->id)->where('decision','revision')->where('created_at','>=',now()->subDay())->count()>=3,429,'Maximaal drie aanpassingen per 24 uur.');
   DB::table('lightspot_reviews')->insert(['submission_id'=>$submission->id,'decision'=>'revision','note'=>json_encode(['title'=>$submission->title,'body'=>$submission->body,'reason'=>$submission->reason],JSON_UNESCAPED_UNICODE),'created_at'=>now(),'updated_at'=>now()]);
   $submission->update($v+['status'=>'pending','ai_retry_at'=>null,'ai_wait_reason'=>null,'translations'=>null,'confidence'=>null,'reason'=>null,'ai_error'=>false,'moderated_by'=>null,'moderator_note'=>null,'appealed_at'=>null,'revision'=>$submission->revision+1]);
  });}finally{$lock->release();}ProcessLightspot::dispatch($submission->id);return redirect('/mijn-lichtpuntjes')->with('status','Je aangepaste verhaal wordt opnieuw gekeurd.');
 }
 public function appeal(Request $r,LightspotSubmission $submission){
  abort_unless($submission->user_id===$r->user()->id,403);$lock=Cache::lock('lichtpunt-submission-'.$submission->id,2100);abort_unless($lock->get(),409);
  try{$submission->refresh();abort_unless($submission->status==='rejected'&&!$submission->appealed_at,422);$submission->update(['status'=>'doubt','appealed_at'=>now()]);}finally{$lock->release();}
  return back()->with('status','Een beheerder kijkt opnieuw naar je verhaal.');
 }
 public function admin(Request $r){
  abort_unless($r->user()->is_admin,403);$v=$r->validate(['status'=>'sometimes|in:review,doubt,pending,rejected,image_wait,published,reported']);$filter=$v['status']??'review';
  $q=LightspotSubmission::with(['user','reports','reviews'=>fn($q)=>$q->latest('id')->limit(10)]);if($filter==='reported')$q->whereHas('reports',fn($q)=>$q->where('resolved',false));elseif($filter==='review')$q->whereIn('status',['pending','doubt','image_wait']);else $q->where('status',$filter);
  $counts=LightspotSubmission::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status');$counts['review']=LightspotSubmission::whereIn('status',['pending','doubt','image_wait'])->count();$counts['reported']=LightspotSubmission::whereHas('reports',fn($q)=>$q->where('resolved',false))->count();
  return view('community.lightspot-admin',['filter'=>$filter,'submissions'=>$q->latest()->paginate(10)->withQueryString(),'counts'=>$counts]);
 }
 public function decide(Request $r,LightspotSubmission $submission,LightspotProcessor $processor){
  abort_unless($r->user()->is_admin,403);$v=$r->validate(['decision'=>'required|in:image_wait,rejected,doubt','note'=>'required|string|min:3|max:1000']);
  $processor->decide($submission,$r->user(),$v['decision'],$v['note']);if($v['decision']==='image_wait')ProcessLightspot::dispatch($submission->id);
  return back()->with('status','Beslissing opgeslagen. Goedgekeurde verhalen krijgen eerst een illustratie.');
 }
 public function report(Request $r,\App\Models\Article $article){
  $this->author($r);abort_unless(\App\Models\Article::visible()->whereKey($article->id)->where('origin','community')->exists(),404);
  $v=$r->validate(['note'=>'required|string|min:5|max:1000']);$s=LightspotSubmission::where('article_id',$article->id)->where('status','published')->firstOrFail();
  \App\Models\LightspotReport::firstOrCreate(['submission_id'=>$s->id,'user_id'=>$r->user()->id],['note'=>$v['note']]);
  return response()->json(['message'=>app()->getLocale()==='en'?'Your report was sent privately to the administrator.':'Je melding is privé naar de beheerder gestuurd.']);
 }
}
