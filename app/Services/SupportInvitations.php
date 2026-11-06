<?php
namespace App\Services;
use App\Models\{User,SupportPledge,SupportPayment};
use App\Support\CookieChoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
use Carbon\CarbonImmutable;

class SupportInvitations
{
 public const COOKIE='lichtpunt_support_preference';
 private function defaults():array {return ['visits'=>0,'last_day'=>'','paused_until'=>0,'last_shown_at'=>0,'popup_shown_at'=>0];}
 private function state(Request $r):array {
  if($r->attributes->has('support_guest_state'))return $r->attributes->get('support_guest_state');
  $raw=json_decode((string)$r->cookie(self::COOKIE),true);$s=$this->defaults();
  if(is_array($raw)){foreach(['visits','paused_until','last_shown_at','popup_shown_at'] as $k)if(is_int($raw[$k]??null)&&$raw[$k]>=0)$s[$k]=min($raw[$k],$k==='visits'?100000:PHP_INT_MAX);if(preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($raw['last_day']??'')))$s['last_day']=$raw['last_day'];}
  return $s;
 }
 private function preference(Request $r):array {
  if(!$r->user())return $this->state($r);
  return array_merge($this->defaults(),(array)(DB::table('support_invitation_preferences')->where('user_id',$r->user()->id)->first()??[]));
 }
 public function prepare(Request $r):void {
  if(!$r->isMethod('GET')||$r->expectsJson()||!$this->page($r)||!Schema::hasTable('support_invitation_preferences'))return;
  if(!$r->user()&&CookieChoice::allowed($r)){
   $s=$this->state($r);$today=now('Europe/Amsterdam')->toDateString();
   if($s['last_day']!==$today){$s['visits']++;$s['last_day']=$today;$r->attributes->set('support_guest_dirty',true);}
   $r->attributes->set('support_guest_state',$s);
  }
  $placement=$r->is('profiel')?'account':'home';
  $decision=$this->decision($r,$placement);
  $r->attributes->set('support_invitation',$decision?array_merge($decision,['placement'=>$placement]):null);
 }
 public function page(Request $r):bool {return $r->is('/','en')&&max(1,(int)$r->query('page',1))===1||$r->is('profiel')&&$r->query('section','overview')==='overview';}
 public function decision(Request $r,string $placement='home'):?array {
  if(!Schema::hasTable('support_invitation_preferences'))return null;
  $u=$r->user();if($u&&(!$u->hasVerifiedEmail()||$u->is_suspended||!$u->community_accepted_at))return null;
  // Guests without the existing optional cookie permission are not counted.
  if(!$u&&!CookieChoice::allowed($r))return null;
  $p=$this->preference($r);$time=now()->timestamp;
  if((int)$p['paused_until']>$time||(int)$p['last_shown_at']>now()->subDays(7)->timestamp)return null;
  if($u){
   $mode=app(SupportPayments::class)->mode();
   $pledges=SupportPledge::where('user_id',$u->id)->where('mode',$mode)->get();
   foreach($pledges->where('frequency','monthly') as $pledge){
    if(!$pledge->stopped_at&&!in_array($pledge->subscription_status,['canceled','completed'],true)&&($pledge->status==='paid'||in_array($pledge->subscription_status,['active','pending','suspended'],true)))return null;
    $stopped=$pledge->stopped_at?:((in_array($pledge->subscription_status,['canceled','completed'],true))?$pledge->updated_at:null);
    if($stopped&&$stopped->gt(now()->subDays(60)))return null;
   }
   if(SupportPayment::whereIn('pledge_id',$pledges->where('frequency','once')->pluck('id'))->where('status','paid')->where('paid_at','>',now()->subDays(180))->whereRaw('cents > refunded_cents + charged_back_cents')->exists())return null;
   $today=now('Europe/Amsterdam')->toDateString();
   $visits=DB::table('user_visits')->where('user_id',$u->id)->where('visit_date','<=',$today)->count();
   if($r->isMethod('GET')&&!DB::table('user_visits')->where('user_id',$u->id)->where('visit_date',$today)->exists())$visits++;
  }else $visits=(int)$p['visits'];
  if($visits<5)return null;
  return ['kind'=>$placement==='home'&&$visits>=10&&!(int)$p['popup_shown_at']?'popup':'inline','visits'=>$visits];
 }
 public function pause(Request $r,int $days=90):void {
  if(!Schema::hasTable('support_invitation_preferences'))return;
  $until=now()->addDays($days)->timestamp;
  if($r->user()){
   DB::transaction(function()use($r,$until){User::whereKey($r->user()->id)->lockForUpdate()->first();DB::table('support_invitation_preferences')->insertOrIgnore(['user_id'=>$r->user()->id]);$p=DB::table('support_invitation_preferences')->where('user_id',$r->user()->id)->first();DB::table('support_invitation_preferences')->where('user_id',$r->user()->id)->update(['paused_until'=>max($until,(int)$p->paused_until)]);});
  }elseif(CookieChoice::allowed($r)){$s=$this->state($r);$s['paused_until']=max($until,$s['paused_until']);$r->attributes->set('support_guest_state',$s);$r->attributes->set('support_guest_dirty',true);}
 }
 public function claim(Request $r,string $kind,string $placement='home'):bool {
  $write=function()use($r,$kind,$placement){$decision=$this->decision($r,$placement);if(!$decision||$decision['kind']!==$kind)return false;
   $values=['last_shown_at'=>now()->timestamp];
   if($kind==='popup')$values+=['popup_shown_at'=>now()->timestamp,'paused_until'=>now()->addDays(90)->timestamp];
   if($r->user())DB::table('support_invitation_preferences')->where('user_id',$r->user()->id)->update($values);
   else{$r->attributes->set('support_guest_state',array_merge($this->state($r),$values));$r->attributes->set('support_guest_dirty',true);}
   return true;
  };
  if(!$r->user())return $write();
  return DB::transaction(function()use($r,$write){User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();DB::table('support_invitation_preferences')->insertOrIgnore(['user_id'=>$r->user()->id]);return $write();},3);
 }
 public function finish(Request $r,$response):void {
  if($response->getStatusCode()<400&&$r->attributes->get('support_guest_dirty')&&CookieChoice::allowed($r))$response->headers->setCookie(cookie(self::COOKIE,json_encode($this->state($r),JSON_THROW_ON_ERROR),180*24*60,'/',null,$r->isSecure(),true,false,'lax'));
  if(!CookieChoice::allowed($r)&&$r->cookie(self::COOKIE))$response->headers->clearCookie(self::COOKIE,'/');
 }
}
