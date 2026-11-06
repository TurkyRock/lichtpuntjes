<?php
namespace App\Services;
use App\Models\Article;
use App\Support\StorySeo;
use Illuminate\Support\Facades\{Cache,DB};
class DailySpotlight {
 public function edition():string {$date=now('Europe/Amsterdam');if($date->hour<7)$date->subDay();return $date->toDateString();}
 private function usable(?Article $a):bool {return $a&&StorySeo::readable($a)&&count($a->nl['body']??[])>0&&count($a->en['body']??[])>0&&StorySeo::safeUrl($a->photo['url']??null)&&app(ArticlePhotoFinder::class)->available($a);}
 private function category(Article $a):string {return $a->origin==='community'?'community':(string)$a->category;}
 public function choose(bool $replace=false,?Article $manual=null):?object {
  $date=$this->edition();$current=DB::table('daily_spotlights')->where('edition_date',$date)->first();
  $existing=$current?Article::visible()->find($current->article_id):null;
  if(!$replace&&!$manual&&$this->usable($existing))return $current;
  $lock=Cache::lock('lichtpunt-daily-spotlight',60);
  if(!$lock->get())return $this->usable($existing)?$current:null;
  try {
   $current=DB::table('daily_spotlights')->where('edition_date',$date)->first();
   $existing=$current?Article::visible()->find($current->article_id):null;
   if(!$replace&&!$manual&&$this->usable($existing))return $current;
   $previous=DB::table('daily_spotlights')->where('edition_date','<',$date)->orderByDesc('edition_date')->first();
   $chosen=null;$isDaily=true;
   if($manual){abort_unless(Article::visible()->whereKey($manual->id)->exists()&&$this->usable($manual),422,'Kies een geplaatst bericht met complete tekst en illustratie.');$chosen=$manual;}
   else {
    $used=DB::table('daily_spotlights')->where('edition_date','>=',now('Europe/Amsterdam')->subDays(30)->toDateString())->whereNotNull('article_id')->pluck('article_id')->all();
    $query=Article::visible()->where('daily_score','>=',75)->whereBetween('published_at',[now()->subDays(7),now()])->whereNotIn('id',$used);
    if($previous?->category)$query->where(function($q)use($previous){if($previous->category==='community')$q->where('origin','!=','community');else $q->where('origin','community')->orWhere('category','!=',$previous->category);});
    $query->orderByRaw('CASE WHEN published_at >= ? THEN 0 ELSE 1 END',[now()->subHours(48)])->orderByDesc('daily_score')->orderByDesc('published_at')->orderByDesc('id');
    foreach($query->cursor() as $candidate)if($this->usable($candidate)){$chosen=$candidate;break;}
   }
   if(!$chosen){
    // Keep a valid current edition during an unsuccessful manual replacement.
    if($this->usable($existing))return $current;
    $fallback=$previous?Article::visible()->find($previous->article_id):null;
    if($this->usable($fallback)){$chosen=$fallback;$isDaily=false;}
   }
   if(!$chosen)return null;
   DB::table('daily_spotlights')->updateOrInsert(['edition_date'=>$date],['article_id'=>$chosen->id,'category'=>$this->category($chosen),'is_daily'=>$isDaily,'manual'=>$manual!==null,'created_at'=>$current->created_at??now(),'updated_at'=>now()]);
   return DB::table('daily_spotlights')->where('edition_date',$date)->first();
  }finally{$lock->release();}
 }
 public function highlights():array {
  $edition=$this->choose();$result=[];$used=[];
  if($edition&&($a=Article::visible()->find($edition->article_id))&&$this->usable($a)){$result[]=['kind'=>$edition->is_daily?'daily':'featured','article'=>$a];$used[]=$a->id;}
  $since=now()->subDays(7);$day=now('Europe/Amsterdam')->subDays(6)->toDateString();
  foreach(['reads','likes'] as $kind){
   $counts=$kind==='reads'?DB::table('article_reads')->where('read_date','>=',$day):DB::table('user_article_likes')->where('created_at','>=',$since);
   $counts=$counts->select('article_id')->selectRaw('COUNT(*) as weekly_total')->groupBy('article_id');
   $ranked=Article::visible()->joinSub($counts,'weekly',fn($j)=>$j->on('articles.id','=','weekly.article_id'))->whereNotIn('articles.id',$used)->orderByDesc('weekly.weekly_total')->orderByDesc('articles.published_at')->orderByDesc('articles.id')->select('articles.*');
   $selected=null;
   foreach($ranked->cursor() as $a)if($this->usable($a)){$selected=$a;break;}
   $label=$kind;
   if(!$selected){$label=$kind==='likes'?'likes_total':'discover';$fallback=Article::visible()->whereNotIn('id',$used);if($kind==='likes')$fallback->orderByDesc('likes_count');foreach($fallback->orderByDesc('published_at')->orderByDesc('id')->limit(30)->get() as $a)if($this->usable($a)){$selected=$a;break;}}
   if($selected){$result[]=['kind'=>$label,'article'=>$selected];$used[]=$selected->id;}
  }
  while(count($result)<3){
   $selected=null;foreach(Article::visible()->whereNotIn('id',$used)->orderByDesc('published_at')->orderByDesc('id')->limit(30)->get() as $a)if($this->usable($a)){$selected=$a;break;}
   if(!$selected)break;$result[]=['kind'=>'discover','article'=>$selected];$used[]=$selected->id;
  }
  return $result;
 }
}
