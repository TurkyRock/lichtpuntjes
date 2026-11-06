<?php
namespace App\Http\Controllers;
use App\Models\{Article,Comment,User};
use App\Services\{CommentConversations,CommentModerator};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CommentConversationController extends Controller
{
 public function heart(Request $r,Comment $comment){
  abort_unless($r->user()->community_accepted_at,403);$v=$r->validate(['liked'=>'required|boolean']);
  $result=DB::transaction(function()use($r,$comment,$v){
   User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();$c=Comment::whereKey($comment->id)->visible()->lockForUpdate()->first();abort_unless($c&&Article::visible()->whereKey($c->article_id)->exists(),404);abort_if($c->user_id===$r->user()->id,422,'Je kunt geen hartje geven aan je eigen reactie.');
   $root=app(CommentConversations::class)->root($c);abort_unless(Comment::visible()->whereKey($root->id)->exists(),404);
   if($v['liked'])DB::table('comment_hearts')->insertOrIgnore(['comment_id'=>$c->id,'user_id'=>$r->user()->id,'created_at'=>now()]);else DB::table('comment_hearts')->where('comment_id',$c->id)->where('user_id',$r->user()->id)->delete();
   return ['comment_id'=>(string)$c->id,'liked'=>(bool)$v['liked'],'hearts_count'=>$c->hearts()->where('users.is_suspended',false)->count()];
  },3);
  return $r->expectsJson()?response()->json($result):back();
 }
 public function reply(Request $r,Comment $comment,CommentModerator $moderator){
  abort_unless(Comment::visible()->whereKey($comment->id)->exists(),404);$root=app(CommentConversations::class)->root($comment);abort_unless(Comment::visible()->whereKey($root->id)->exists(),404);
  $r->merge(['parent_id'=>$comment->id]);$response=app(CommunityController::class)->store($r,$comment->article,$moderator);
  if($r->expectsJson())return $response;
  $data=$response->getData(true);$en=app()->getLocale()==='en';$message=match($data['status']){'approved'=>$en?'Your reply is published.':'Je antwoord staat bij het gesprek.','rejected'=>$en?'Your reply was not published. Your private feedback is shown below.':'Je antwoord is niet geplaatst. Je privéfeedback staat hieronder.',default=>$en?'Your reply is awaiting review.':'Je antwoord wacht op controle.'};
  return redirect('/profiel?section=comments&lang='.app()->getLocale().'#conversation-'.$root->id)->with('status',$message);
 }
}
