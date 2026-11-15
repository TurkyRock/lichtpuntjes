<?php
namespace App\Services\Social;
use App\Models\{Article,SocialPublication,SocialDelivery,User};
use App\Services\ArticlePhotoFinder;
use App\Support\StorySeo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{Cache,DB};
class Planner {
 public function day(?string $date=null):CarbonImmutable {return $date?CarbonImmutable::createFromFormat('!Y-m-d',$date,'Europe/Amsterdam'):CarbonImmutable::now('Europe/Amsterdam')->startOfDay();}
 public function due(string $date,string $kind):CarbonImmutable {return $this->day($date)->setTimeFromTimeString(config('social-publishing.slots.'.$kind.'.time'));}
 public function usable(?Article $a):bool {return $a&&Article::visible()->whereKey($a->id)->exists()&&StorySeo::readable($a)&&app(ArticlePhotoFinder::class)->available($a);}
 public function ranked(string $date,string $kind):\Illuminate\Database\Eloquent\Builder {
  $day=$this->day($date);$q=Article::visible()->where('published_at','<=',now())->select('articles.*');
  if($kind==='daily'){
   $q->where('daily_score','>=',75)->whereBetween('published_at',[$day->subDays(7)->utc(),$day->endOfDay()->utc()]);
   $featured=DB::table('daily_spotlights')->whereDate('edition_date',$date)->value('article_id');
   return $q->orderByRaw('CASE WHEN articles.id = ? THEN 0 ELSE 1 END',[(int)$featured])->orderByDesc('daily_score')->orderByDesc('published_at')->orderByDesc('id');
  }
  if($kind==='reads')$counts=DB::table('article_reads')->whereDate('read_date',$date)->select('article_id')->selectRaw('COUNT(*) AS score')->groupBy('article_id');
  else $counts=DB::table('user_article_likes')->whereBetween('created_at',[$day->utc(),$day->endOfDay()->utc()])->select('article_id')->selectRaw('COUNT(*) AS score')->groupBy('article_id');
  return $q->joinSub($counts,'social_counts',fn($j)=>$j->on('articles.id','=','social_counts.article_id'))->orderByDesc('social_counts.score')->orderByDesc('articles.published_at')->orderByDesc('articles.id');
 }
 public function candidates(string $date,string $kind,?SocialPublication $current=null,array $extra=[]):array {
  $claimed=SocialPublication::whereNotNull('article_id')->when($current,fn($q)=>$q->whereKeyNot($current->id))->pluck('article_id')->merge($extra)->all();$result=[];$rank=0;
  foreach($this->ranked($date,$kind)->cursor() as $a){$rank++;if(in_array($a->id,$claimed)||!$this->usable($a))continue;$result[]=['article'=>$a,'rank'=>$rank];if(count($result)>=20)break;}
  return $result;
 }
 public function board(string $date):array {
  $rows=SocialPublication::with('deliveries')->whereDate('edition_date',$date)->get()->keyBy('kind');$cards=[];$extra=[];
  foreach(config('social-publishing.slots') as $kind=>$slot){$p=$rows->get($kind);$a=$p?->article;
   $candidates=$this->candidates($date,$kind,$p,$extra);$candidate=$p?null:($candidates[0]??null);$selected=$p?$a:($candidate['article']??null);
   if($selected)$extra[]=$selected->id;
   $cards[$kind]=['kind'=>$kind,'slot'=>$slot,'publication'=>$p,'article'=>$selected,'rank'=>$p?->selection_rank??($candidate['rank']??null),'candidates'=>$candidates,'usable'=>$this->usable($selected)];
  }return $cards;
 }
 public function prepare(string $date,string $kind,?int $manual=null):?SocialPublication {
  abort_unless(isset(config('social-publishing.slots')[$kind]),404);
  $lock=Cache::lock('social-publication-planner',120);if(!$lock->get())throw new \RuntimeException('Er wordt al een sociale selectie voorbereid.');
  try {return DB::transaction(function()use($date,$kind,$manual){
   // Only sent articles remain claimed forever. Unsent drafts from older days expire.
   SocialPublication::whereDate('edition_date','<',now('Europe/Amsterdam')->toDateString())->whereNull('first_shared_at')->whereNotNull('article_id')->update(['article_id'=>null,'status'=>'cancelled']);
   $p=SocialPublication::whereDate('edition_date',$date)->where('kind',$kind)->lockForUpdate()->first();
   abort_if($p?->first_shared_at,409,'Deze publicatieronde is al gedeeld en blijft ongewijzigd.');
   if($p&&$manual===null&&$this->usable($p->article))return $p;
   $candidates=$this->candidates($date,$kind,$p);$choice=$manual?collect($candidates)->first(fn($c)=>$c['article']->id===$manual):($candidates[0]??null);
   if($manual)abort_unless($choice,422,'Kies een geschikt artikel dat nog niet is gedeeld of gereserveerd.');
   if(!$choice)return null;
   $a=$choice['article'];$snapshot=['title'=>$a->nl['title'],'teaser'=>mb_substr(strip_tags($a->nl['teaser']??''),0,350),'url'=>StorySeo::absolute(StorySeo::path($a)),'photo'=>$a->photo,'template'=>'paper-v1'];
   $p??=new SocialPublication(['edition_date'=>$date,'kind'=>$kind]);$p->fill(['article_id'=>$a->id,'status'=>'ready','due_at'=>$this->due($date,$kind)->utc(),'snapshot'=>$snapshot,'captions'=>$this->captions($kind,$snapshot),'selection_rank'=>$choice['rank'],'selection_note'=>$choice['rank']>1?'Hogere kandidaten zijn al gedeeld, gereserveerd of niet geschikt.':'Hoogste geschikte kandidaat die nog niet is gedeeld.']);$p->save();
   foreach(array_keys(config('social-publishing.channels')) as $channel)SocialDelivery::firstOrCreate(['publication_id'=>$p->id,'channel'=>$channel]);
   return $p;
  });}finally{$lock->release();}
 }
 public function captions(string $kind,array $snapshot):array {
  $intro=config('social-publishing.slots.'.$kind.'.intro');$body=$intro." ☀\n\n".$snapshot['title']."\n".$snapshot['teaser'];$result=[];
  foreach(array_keys(config('social-publishing.channels')) as $channel)$result[$channel]=$body."\n\n".(in_array($channel,['instagram','tiktok'])?'Haal meer lichtpuntjes uit je dag. Lees verder via de link in ons profiel.':'Benieuwd naar nog meer lichtpuntjes? '.$snapshot['url']);
  return $result;
 }
 public function saveCaption(SocialPublication $p,string $channel,string $text):void {
  DB::transaction(function()use($p,$channel,$text){$p=SocialPublication::whereKey($p->id)->lockForUpdate()->firstOrFail();abort_if($p->first_shared_at,409,'Een gedeelde ronde kun je niet meer wijzigen.');$captions=$p->captions;$captions[$channel]=$text;$p->update(['captions'=>$captions]);});
 }
 public function postUrl(string $channel,string $url):?string {
  $u=parse_url($url);if(!$u||($u['scheme']??'')!=='https'||(isset($u['user'])||isset($u['pass']))||isset($u['port']))return null;
  $host=strtolower($u['host']??'');$path=$u['path']??'';
  $hosts=['facebook'=>['facebook.com','www.facebook.com','m.facebook.com'],'instagram'=>['instagram.com','www.instagram.com'],'linkedin'=>['linkedin.com','www.linkedin.com'],'tiktok'=>['tiktok.com','www.tiktok.com']];
  if(!in_array($host,$hosts[$channel]??[],true))return null;
  $valid=match($channel){'facebook'=>preg_match('~/(posts/|permalink\.php|photo\.php|photos/|reel/|videos/|share/)~',$path),'instagram'=>preg_match('~^/(p|reel)/[^/]+/?$~',$path),'linkedin'=>preg_match('~^/(feed/update/|posts/)~',$path),'tiktok'=>preg_match('~^/@[^/]+/(video|photo)/\d+/?$~',$path),default=>false};
  if(!$valid)return null;
  parse_str($u['query']??'',$query);$query=array_intersect_key($query,array_flip(['story_fbid','id','fbid','v']));
  return 'https://'.$host.$path.($query?'?'.http_build_query($query):'');
 }
 public function register(SocialPublication $publication,string $channel,string $url,User $actor):SocialDelivery {
  $clean=$this->postUrl($channel,$url);abort_unless($clean,422,'Gebruik de HTTPS-link van de echte post op dit kanaal, niet de profielpagina.');
  return DB::transaction(function()use($publication,$channel,$clean,$actor){
   $p=SocialPublication::whereKey($publication->id)->lockForUpdate()->firstOrFail();abort_unless($this->usable($p->article)&&$p->snapshot,422,'Het bronartikel is niet meer openbaar beschikbaar.');
   $d=SocialDelivery::where('publication_id',$p->id)->where('channel',$channel)->lockForUpdate()->firstOrFail();
   if($d->external_url!==$clean){DB::table('social_delivery_events')->insert(['delivery_id'=>$d->id,'user_id'=>$actor->id,'action'=>$d->external_url?'link_corrected':'manual_registered','url'=>$clean,'created_at'=>now()]);$d->fill(['external_url'=>$clean,'source'=>'manual','status'=>'published','published_at'=>$d->published_at??now(),'checked_at'=>null,'checked_by'=>null])->save();}
   $p->first_shared_at??=now();$p->status=$p->deliveries()->where('status','published')->count()===count(config('social-publishing.channels'))?'published':'partial';$p->save();return $d;
  });
 }
}
