<?php
namespace App\Services;
use App\Models\{Comment,User};
use Illuminate\Support\Facades\DB;
class CommentConversations
{
 public function root(Comment $comment):Comment {
  $seen=[];$current=$comment;
  while($current->parent_id){abort_if(isset($seen[$current->id])||count($seen)>100,422,'Dit gesprek is niet beschikbaar.');$seen[$current->id]=true;$parent=Comment::find($current->parent_id);if(!$parent||$parent->article_id!==$comment->article_id)break;$current=$parent;}
  return $current;
 }
 public function page(User $user){
  $roots=Comment::where('user_id',$user->id)->selectRaw('COALESCE(thread_root_id,id)');
  $visible=fn($q)=>$q->where(fn($q)=>$q->where('user_id',$user->id)->orWhere(fn($q)=>$q->visible()));
  $hearts=fn($q)=>$q->where('users.is_suspended',false);
  return Comment::whereIn('id',$roots)->with(['article.author','user.profilePhoto','threadReplies'=>function($q)use($visible,$hearts,$user){$visible($q);$q->with('user.profilePhoto')->withCount(['hearts'=>$hearts])->withExists(['hearts as hearted'=>fn($q)=>$q->where('users.id',$user->id)])->oldest('id');}])->withCount(['hearts'=>$hearts,'threadReplies as visible_reply_count'=>fn($q)=>$q->visible()])->withExists(['hearts as hearted'=>fn($q)=>$q->where('users.id',$user->id)])->withMax(['threadReplies as last_reply_at'=>$visible],'created_at')->orderByRaw('COALESCE(last_reply_at,comments.created_at) DESC')->orderByDesc('id')->paginate(3)->appends(['section'=>'comments','lang'=>app()->getLocale()]);
 }
 public function totals(User $user):array {
  return [
   'hearts'=>DB::table('comment_hearts as h')->join('comments as c','c.id','=','h.comment_id')->join('users as u','u.id','=','h.user_id')->where('c.user_id',$user->id)->where('c.status','approved')->where('u.is_suspended',false)->count(),
   'replies'=>Comment::visible()->where('user_id','!=',$user->id)->whereHas('parent',fn($q)=>$q->where('user_id',$user->id))->count(),
  ];
 }
}
