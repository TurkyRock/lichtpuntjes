<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB,Hash};
class AccountPrivacyController extends Controller {
 public function export(Request $r){
  $r->validate(['current_password'=>'required|current_password']);$u=$r->user();
  $data=['exported_at'=>now()->toIso8601String(),'account'=>['name'=>$u->name,'email'=>$u->email,'created_at'=>$u->created_at,'preferences'=>$u->news_preferences],
   'moments'=>\App\Models\PositiveMoment::withTrashed()->where('user_id',$u->id)->get()->map(fn($m)=>['id'=>$m->id,'body'=>$m->body,'visibility'=>$m->visibility,'status'=>$m->status,'date'=>$m->moment_date,'deleted_at'=>$m->deleted_at]),
   'comments'=>$u->comments()->get()->map(fn($c)=>['id'=>$c->id,'body'=>$c->body,'status'=>$c->status,'created_at'=>$c->created_at]),
   'stories'=>$u->submissions()->get()->map(fn($s)=>['id'=>$s->id,'title'=>$s->title,'body'=>$s->body,'story_place'=>$s->story_place,'story_region'=>$s->story_region,'status'=>$s->status]),
   'support'=>\App\Models\SupportPledge::where('user_id',$u->id)->with('payments')->get()->map(fn($p)=>['amount_cents'=>$p->cents,'frequency'=>$p->frequency,'status'=>$p->status,'stopped_at'=>$p->stopped_at,'payments'=>$p->payments->map(fn($p)=>['amount_cents'=>$p->cents,'status'=>$p->status,'paid_at'=>$p->paid_at])]),
   'recommendation_signals'=>DB::table('user_news_signals')->where('user_id',$u->id)->get(['article_id','kind','category','created_at']),
   'activity'=>DB::table('lightpoint_events')->where('user_id',$u->id)->get(),
   'privacy_requests'=>DB::table('privacy_requests')->where('user_id',$u->id)->get()];
  return response()->streamDownload(fn()=>print(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)),'mijn-lichtpuntjes.json',['Content-Type'=>'application/json','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
 }
 public function eraseContribution(Request $r,string $kind,int $id){
  $r->validate(['confirm'=>'accepted']);
  $model=$kind==='comment'?\App\Models\Comment::class:\App\Models\LightspotSubmission::class;
  DB::transaction(function()use($r,$model,$kind,$id){
   $c=$model::where('user_id',$r->user()->id)->lockForUpdate()->findOrFail($id);
   \App\Models\LocalAiJob::query()->delete();
   if($kind==='story'&&$c->article_id){$a=\App\Models\Article::where('author_id',$r->user()->id)->find($c->article_id);if($a){\App\Models\LaptopImageJob::where('target_type','article')->where('target_id',$a->id)->delete();if(\Illuminate\Support\Facades\Schema::hasTable('social_publications'))\App\Models\SocialPublication::where('article_id',$a->id)->delete();$a->delete();}}
   if($kind==='comment')app(\App\Services\Lightpoints::class)->revokeComment($c);
   DB::table('contribution_consents')->where('kind',$kind)->where('target_id',$id)->delete();
   DB::table('content_reports')->where('kind',$kind==='story'?'article':'comment')->where('target_id',$kind==='story'?($c->article_id??0):$id)->delete();
   $c->delete();
  });return back()->with('status',app()->getLocale()==='en'?'Contribution permanently removed.':'Bijdrage definitief verwijderd.');
 }
 public function destroy(Request $r){
  abort_if($r->user()->is_admin,422,'Het beheeraccount kan hier niet worden verwijderd.');
  $r->validate(['current_password'=>'required|current_password','confirm_delete'=>'accepted','support_choice'=>'required|in:stop,keep','keep_support_consent'=>'accepted_if:support_choice,keep']);
  try{app(\App\Services\AccountErasure::class)->erase($r->user(),$r->input('support_choice')==='keep');}
  catch(\Throwable $e){return back()->withErrors(['delete'=> 'Verwijderen is niet afgerond. Controleer of maandelijkse steun gestopt kan worden, of de beheerlink per e-mail kan worden verzonden. Probeer later opnieuw of mail '.config('privacy.email').'.']);}
  Auth::guard()->logoutCurrentDevice();$r->session()->invalidate();$r->session()->regenerateToken();
  return redirect('/')->with('status',app()->getLocale()==='en'?'Your account and contributions have been deleted.':'Je account en bijdragen zijn verwijderd.');
 }
}
