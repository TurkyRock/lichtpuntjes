<?php
namespace App\Http\Controllers;
use App\Models\{SupportPayment,SupportPledge};
use App\Services\SupportPayments;
use Illuminate\Http\Request;
use Carbon\CarbonImmutable;
class AdminDonationsController extends Controller {
 public function index(Request $r) {
  abort_unless($r->user()?->is_admin,403);
  $v=$r->validate(['mode'=>'nullable|in:live,test','status'=>'nullable|in:all,paid,open,pending,authorized,failed,canceled,expired,refunded,chargeback','from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d'.($r->filled('from')?'|after_or_equal:from':''),'q'=>'nullable|string|max:100','user_id'=>'nullable|integer|exists:users,id']);
  $mode=$v['mode']??app(SupportPayments::class)->mode();$status=$v['status']??'all';$search=trim($v['q']??'');
  $base=SupportPayment::whereHas('pledge',fn($q)=>$q->where('mode',$mode));
  if(!empty($v['user_id']))$base->whereHas('pledge',fn($q)=>$q->where('user_id',$v['user_id']));
  // Periode voor alle totalen en de lijst: betaaldatum, anders aanmaakdatum.
  if(!empty($v['from']))$base->whereRaw('COALESCE(paid_at, support_payments.created_at) >= ?',[CarbonImmutable::parse($v['from'],'Europe/Amsterdam')->startOfDay()->utc()]);
  if(!empty($v['to']))$base->whereRaw('COALESCE(paid_at, support_payments.created_at) < ?',[CarbonImmutable::parse($v['to'],'Europe/Amsterdam')->addDay()->startOfDay()->utc()]);
  if($search!=='')$base->whereHas('pledge.user',fn($u)=>$u->where(fn($u)=>$u->where('name','like','%'.$search.'%')->orWhere('email','like','%'.$search.'%')));
  $paid=(clone $base)->where('status','paid')->whereNotNull('paid_at');
  $totals=(clone $paid)->selectRaw('COUNT(*) as payments, COALESCE(SUM(cents),0) as gross, COALESCE(SUM(refunded_cents),0) as refunds, COALESCE(SUM(charged_back_cents),0) as chargebacks, COALESCE(SUM(CASE WHEN cents > refunded_cents + charged_back_cents THEN cents - refunded_cents - charged_back_cents ELSE 0 END),0) as net')->first();
  $monthStart=now('Europe/Amsterdam')->startOfMonth()->utc();$monthEnd=now('Europe/Amsterdam')->startOfMonth()->addMonth()->utc();
  $monthCents=(clone $paid)->where('paid_at','>=',$monthStart)->where('paid_at','<',$monthEnd)->selectRaw('COALESCE(SUM(CASE WHEN cents > refunded_cents + charged_back_cents THEN cents - refunded_cents - charged_back_cents ELSE 0 END),0) as total')->value('total');
  $active=SupportPledge::where('mode',$mode)->where('frequency','monthly')->where('subscription_status','active')->whereNull('stopped_at');
  if(!empty($v['user_id']))$active->where('user_id',$v['user_id']);
  $activeCount=(clone $active)->count();$monthlyCents=(int)(clone $active)->sum('cents');
  $pendingCount=(clone $base)->whereIn('status',['open','pending','authorized'])->count();
  $failedCount=(clone $base)->whereIn('status',['failed','canceled','expired'])->count();
  $list=clone $base;
  if($status==='refunded')$list->where('refunded_cents','>',0);
  elseif($status==='chargeback')$list->where('charged_back_cents','>',0);
  elseif($status!=='all')$list->where('status',$status);
  $payments=$list->with('pledge.user')->orderByRaw('COALESCE(paid_at, support_payments.created_at) DESC')->orderByDesc('id')->paginate(20)->withQueryString();
  $subscriptions=SupportPledge::with('user')->where('mode',$mode)->when(!empty($v['user_id']),fn($q)=>$q->where('user_id',$v['user_id']))->where('frequency','monthly')->latest()->paginate(10,['*'],'subscriptions_page')->withQueryString();
  $attempts=SupportPledge::with('user')->where('mode',$mode)->when(!empty($v['user_id']),fn($q)=>$q->where('user_id',$v['user_id']))->whereDoesntHave('payments')->latest()->limit(10)->get();
  return view('admin.donations',compact('mode','status','search','totals','monthCents','activeCount','monthlyCents','pendingCount','failedCount','payments','subscriptions','attempts'));
 }
}
