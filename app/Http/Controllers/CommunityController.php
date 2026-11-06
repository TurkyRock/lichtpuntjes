<?php
namespace App\Http\Controllers;
use App\Models\Article;
use App\Models\Comment;
use App\Services\CommentModerator;
use App\Services\Lightpoints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CommunityController extends Controller {
 private function contributor(Request $r):void {abort_unless($r->user()?->community_accepted_at,403,'Bevestig eerst de huisregels in je profiel.');abort_unless($r->user()->hasVerifiedEmail(),403,'Bevestig eerst je e-mailadres.');}
 public function profile(Request $r,Lightpoints $points){
  $u=$r->user();if(!$u->invite_code)$u->forceFill(['invite_code'=>bin2hex(random_bytes(16))])->save();
  $accountSection=$r->query('section','overview');
  if(!in_array($accountSection,['overview','badges','comments','invite','points'],true))$accountSection='overview';
  $calendarInput=$r->validate(['month'=>'nullable|date_format:Y-m','day'=>'nullable|date_format:Y-m-d']);
  $accountMonth=isset($calendarInput['month'])?\Carbon\CarbonImmutable::createFromFormat('!Y-m',$calendarInput['month'],'Europe/Amsterdam'):\Carbon\CarbonImmutable::now('Europe/Amsterdam')->startOfMonth();
  $today=now('Europe/Amsterdam')->toDateString();$selectedDay=$calendarInput['day']??($accountMonth->format('Y-m')===substr($today,0,7)?$today:$accountMonth->toDateString());
  abort_unless(substr($selectedDay,0,7)===$accountMonth->format('Y-m'),422);
  if($accountSection==='overview')app(\App\Services\ActivityCalendar::class)->visit($u);
  $activityCalendar=$accountSection==='overview'?app(\App\Services\ActivityCalendar::class)->month($u,$accountMonth,$selectedDay):null;
  $recentMoments=$accountSection==='overview'?\App\Models\PositiveMoment::where('user_id',$u->id)->where('status','approved')->orderByDesc('moment_date')->orderByDesc('id')->limit(3)->get():collect();
  $events=DB::table('lightpoint_events')->where('user_id',$u->id)->orderByDesc('id')->limit(25)->get();
  return view(\App\Support\CommunityViews::name('profile'),['pointsDiary'=>$accountSection==='points'?app(\App\Services\PointsDiary::class)->forUser($u):null,'activityCalendar'=>$activityCalendar,'recentMoments'=>$recentMoments,'accountMonth'=>$accountMonth,'user'=>$u,'accountSection'=>$accountSection,'profilePhotoUpload'=>\App\Models\ProfilePhoto::where('user_id',$u->id)->latest('id')->first(),'progress'=>$points->summary($u),'events'=>$events,'achievementKinds'=>DB::table('lightpoint_events')->where('user_id',$u->id)->where('active',true)->distinct()->pluck('kind'),'commentTotals'=>$accountSection==='comments'?app(\App\Services\CommentConversations::class)->totals($u):[], 'comments'=>$accountSection==='comments'?app(\App\Services\CommentConversations::class)->page($u):null]);
 }
 public function accept(Request $r){$r->validate(['intentions'=>'accepted']);$r->user()->forceFill(['community_accepted_at'=>now(),'community_version'=>'2026-10-10'])->save();return back()->with('status','Fijn dat je meedoet aan een vriendelijke plek.');}
 public function comments(Request $r,Article $article){
  abort_unless(Article::visible()->whereKey($article->id)->exists(),404);$v=$r->validate(['page'=>'sometimes|integer|min:1|max:100000','parent_id'=>'nullable|integer|min:1']);
  $parentId=$v['parent_id']??null;
  $visible=fn()=>Comment::where('article_id',$article->id)->visible();
  if($parentId)abort_unless($visible()->whereKey($parentId)->exists(),404);
  $query=$visible();
  if($parentId)$query->where('parent_id',$parentId);
  else $query->where(fn($q)=>$q->whereNull('parent_id')->orWhereDoesntHave('parent',fn($p)=>$p->visible()->where('article_id',$article->id)));
  $comments=$query->with(['user.profilePhoto','parent.user'])->withCount(['hearts'=>fn($q)=>$q->where('users.is_suspended',false),'replies'=>fn($q)=>$q->visible()->where('article_id',$article->id)]);
  if($r->user())$comments->withExists(['hearts as hearted'=>fn($q)=>$q->where('users.id',$r->user()->id)]);
  if($parentId)$comments->oldest('id');else $comments->latest('id');
  $comments=$comments->paginate(20);
  $own=$r->user()?Comment::where('article_id',$article->id)->where('user_id',$r->user()->id)->where('status','!=','approved')->latest()->limit(5)->get(['id','body','status']):[];
  return response()->json(['comments'=>$comments->getCollection()->map(fn($c)=>['id'=>(string)$c->id,'name'=>$c->user->name,'avatar'=>$c->user->avatarUrl(),'body'=>$c->bodyFor(),'date'=>$c->created_at->toIso8601String(),'hearts_count'=>$c->hearts_count,'hearted'=>(bool)$c->hearted,'can_heart'=>(bool)($r->user()?->hasVerifiedEmail()&&$r->user()?->community_accepted_at&&!$r->user()?->is_suspended&&$r->user()?->id!==$c->user_id),'replies_count'=>$c->replies_count,'parent_id'=>$c->parent_id?(string)$c->parent_id:null,'parent'=>($c->parent&&$c->parent->status==='approved'&&!$c->parent->user->is_suspended)?['name'=>$c->parent->user->name,'body'=>mb_substr($c->parent->bodyFor(),0,180)]:null]),'own'=>$parentId?[]:$own,'page'=>$comments->currentPage(),'last_page'=>$comments->lastPage(),'items_total'=>$comments->total(),'total'=>$visible()->count(),'can_comment'=>(bool)($r->user()?->hasVerifiedEmail()&&$r->user()?->community_accepted_at&&!$r->user()?->is_suspended)])->header('Cache-Control','private, no-store');
 }
 public function store(Request $r,Article $article,CommentModerator $moderator){
  $this->contributor($r);abort_unless(Article::visible()->whereKey($article->id)->exists(),404);
  $v=$r->validate(['body'=>'required|string|min:3|max:1000','website'=>'prohibited','parent_id'=>'nullable|integer|min:1']);$body=trim($v['body']);abort_if(mb_strlen($body)<3,422,'Schrijf een iets langere reactie.');
  $c=DB::transaction(function()use($r,$article,$body,$v){
   \App\Models\User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
   abort_if(Comment::where('user_id',$r->user()->id)->where('created_at','>=',now()->subDay())->count()>=20,429,'Vandaag even genoeg glimlachen verstuurd. Probeer morgen weer.');
   abort_if(Comment::where('user_id',$r->user()->id)->where('article_id',$article->id)->where('body',$body)->where('created_at','>=',now()->subDay())->exists(),422,'Die glimlach heb je al verstuurd.');
   $parentId=$v['parent_id']??null;
   if($parentId){$parent=Comment::whereKey($parentId)->where('article_id',$article->id)->where('status','approved')->whereHas('user',fn($q)=>$q->where('is_suspended',false))->lockForUpdate()->first();abort_unless($parent,422,'Deze reactie is niet beschikbaar om op te antwoorden.');}
   $root=$parentId?app(\App\Services\CommentConversations::class)->root($parent):null;
   return Comment::create(['parent_id'=>$parentId,'thread_root_id'=>$root?->id,'user_id'=>$r->user()->id,'article_id'=>$article->id,'body'=>$body,'status'=>'pending']);
  },3);
  $moderator->review($c);$c->refresh();return response()->json(['status'=>$c->status,'message'=>match($c->status){'approved'=>'Je glimlach staat erbij! Een goedgekeurde reactie verdient één lichtpunt (maximaal vijf per dag).','rejected'=>'Deze reactie past niet bij onze huisregels en is niet geplaatst. Probeer een vriendelijke, positieve bijdrage.',default=>'Je reactie wacht op een menselijke blik. Hij verschijnt na goedkeuring.'}],201);
 }
 public function appeal(Request $r,Comment $comment){
  abort_unless($comment->user_id===$r->user()->id,403);
  DB::transaction(function()use($comment){$c=Comment::whereKey($comment->id)->lockForUpdate()->firstOrFail();abort_unless($c->status==='rejected'&&!$c->appealed_at,422);$c->update(['status'=>'doubt','appealed_at'=>now(),'ai_error'=>false]);});return back()->with('status','Je reactie staat nu klaar voor een menselijke blik.');
 }
 public function revise(Request $r,Comment $comment,CommentModerator $moderator,Lightpoints $points){
  $this->contributor($r);abort_unless($comment->user_id===$r->user()->id,403);
  $v=$r->validate(['body'=>'required|string|min:3|max:1000']);$body=trim($v['body']);
  abort_if(mb_strlen($body)<3,422,'Schrijf een iets langere reactie.');
  $lock=\Illuminate\Support\Facades\Cache::lock('lichtpunt-comment-'.$comment->id,90);
  if(!$lock->get())return back()->withErrors(['body'=>'Deze reactie wordt al verwerkt. Probeer het over een moment opnieuw.'])->onlyInput('body','revise_id');
  try {
   DB::transaction(function()use($r,$comment,$body,$points){
    $c=Comment::whereKey($comment->id)->with('article')->lockForUpdate()->firstOrFail();
    abort_unless($c->user_id===$r->user()->id,403);abort_unless($c->status==='rejected',422,'Alleen een afgewezen reactie kan opnieuw worden ingediend.');
    abort_unless(Article::visible()->whereKey($c->article_id)->exists(),422,'Dit nieuwsbericht is niet actief.');
    if($c->body===$body)throw \Illuminate\Validation\ValidationException::withMessages(['body'=>'Pas je reactie eerst aan voordat je die opnieuw laat keuren.']);
    abort_if(DB::table('comment_reviews')->where('comment_id',$c->id)->whereNull('moderator_id')->where('created_at','>=',now()->subDay())->count()>=10,429,'Voor deze reactie zijn er vandaag genoeg nieuwe pogingen geweest.');
    abort_if(Comment::where('user_id',$r->user()->id)->where('article_id',$c->article_id)->where('id','!=',$c->id)->where('body',$body)->where('created_at','>=',now()->subDay())->exists(),422,'Die reactie heb je al verstuurd.');
    DB::table('comment_reviews')->insert(['comment_id'=>$c->id,'moderator_id'=>null,'from_status'=>'rejected','to_status'=>'pending','note'=>'Auteur #'.$r->user()->id.' heeft de tekst aangepast. Vorige tekst: '.$c->body.' | Vorige reden: '.($c->moderator_note?:$c->ai_reason),'created_at'=>now()]);
    $points->revokeComment($c);
    $c->update(['body'=>$body,'status'=>'pending','translations'=>null,'translation_hash'=>null,'translation_state'=>'pending','source_language'=>null,'ai_reason'=>null,'ai_confidence'=>null,'ai_error'=>false,'ai_model'=>null,'moderated_by'=>null,'moderator_note'=>null,'approved_at'=>null,'appealed_at'=>null]);
   },3);
  } finally {$lock->release();}
  $moderator->review($comment);$comment->refresh();
  $en=app()->getLocale()==='en';
  $message=match($comment->status){'approved'=>$en?'Your revised comment was approved and is published.':'Je aangepaste reactie is goedgekeurd en staat erbij!','rejected'=>$en?'This version was not published. See the feedback below.':'Deze versie is niet geplaatst. Bekijk hieronder de feedback.',default=>$en?'Your revised comment is awaiting a human review.':'Je aangepaste reactie wacht op een menselijke blik.'};
  return redirect('/profiel?'.http_build_query(['lang'=>app()->getLocale(),'section'=>'comments']))->with('status',$message);
 }
 public function share(Request $r,Article $article,Lightpoints $points){
  $this->contributor($r);abort_unless(Article::visible()->whereKey($article->id)->exists(),404);
  $awarded=$points->award($r->user(),'share:'.now('Europe/Amsterdam')->toDateString().':'.$article->id,'share',1,'Een lichtpuntje klaargezet om te delen',3);
  return response()->json(['awarded'=>$awarded,'message'=>$awarded?'Een lichtpunt voor je deelactie!':'Deel gerust verder. Voor deze actie of vandaag zijn de deelpunten al verdiend.']);
 }
}
