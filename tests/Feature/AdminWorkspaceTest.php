<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,Article,Comment,PositiveMoment,ProfilePhoto,SupportPledge,SupportPayment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http,Notification,Queue,File,Cache};
use Illuminate\Support\Str;
class AdminWorkspaceTest extends TestCase {
 use RefreshDatabase;
 private int $sequence=0;
 protected function setUp():void {parent::setUp();Cache::flush();Http::preventStrayRequests();Notification::fake();Queue::fake();config(['support.key'=>'live_example','support.enabled'=>false]);}
 private function member(array $extra=[]):User {return User::forceCreate($extra+['name'=>'Zonnetje','email'=>Str::uuid().'@example.test','password'=>'An existing sunny password','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 private function pledge(?User $u,array $extra=[]):SupportPledge {return SupportPledge::create($extra+['id'=>(string)Str::uuid(),'user_id'=>$u?->id,'cents'=>500,'frequency'=>'once','mode'=>'live','status'=>'paid']);}
 private function payment(SupportPledge $p,array $extra=[]):SupportPayment {return SupportPayment::create($extra+['pledge_id'=>$p->id,'provider_id'=>'tr_private_'.++$this->sequence,'cents'=>$p->cents,'status'=>'paid','paid_at'=>now(),'refunded_cents'=>0,'charged_back_cents'=>0]);}
 private function moment(User $u,array $extra=[]):PositiveMoment {return PositiveMoment::create($extra+['user_id'=>$u->id,'body'=>'Een kopje koffie in de zon.','visibility'=>'public','status'=>'doubt','moment_date'=>today(),'ai_accepted_at'=>now(),'reason'=>['nl'=>'Even controleren.']]);}
 private function article(array $extra=[]):Article {return Article::create($extra+['fingerprint'=>(string)Str::uuid(),'origin'=>'news','status'=>'published','category'=>'nature','source_title'=>'Een zonnige nieuwstitel','source_name'=>'Nieuwsbron','source_url'=>'https://example.test/story','published_at'=>now(),'nl'=>['title'=>'Een zonnige nieuwstitel','body'=>['Mooi nieuws.']]]);}
 private function capture($r,string $name):void {if(!getenv('ADMIN_CAPTURE'))return;File::ensureDirectoryExists(base_path('../verification/admin-render'));File::put(base_path('../verification/admin-render/'.$name.'.html'),$r->getContent());}
 public function test_administration_is_separate_from_account_and_every_page_is_protected():void {
  $paths=['/beheer','/beheer/reacties','/beheer/artikelen','/beheer/lichtpuntjes','/beheer/momentjes','/beheer/profielfotos','/beheer/gebruikers','/beheer/donaties','/beheer/gebruikers?filter=privacy'];
  foreach($paths as $path)$this->get($path)->assertRedirect('/inloggen');
  $user=$this->member();$this->actingAs($user);foreach($paths as $path)$this->get($path)->assertForbidden();
  $admin=$this->member(['is_admin'=>true]);$this->actingAs($admin)->get('/profiel')->assertOk()->assertDontSee('href="/beheer',false);
  $r=$this->get('/beheer')->assertOk()->assertSee('De zonneschijncentrale')->assertSee('href="/beheer/donaties?lang=nl"',false)->assertDontSee('href="/privacy/verzoeken',false)->assertDontSee('Beeldproef')->assertDontSee('Privacyverzoeken')->assertHeader('X-Robots-Tag','noindex');$this->capture($r,'overview');
  $this->get('/beheer/beeldproef')->assertNotFound();
  $admin->forceFill(['is_suspended'=>true])->save();$this->get('/beheer/donaties')->assertForbidden();
 }
 public function test_unverified_and_expired_confirmation_are_not_bypassed_by_new_routes():void {
  $admin=$this->member(['is_admin'=>true,'email_verified_at'=>null]);$this->actingAs($admin)->get('/beheer/donaties')->assertRedirect('/email/verify');
  $admin->forceFill(['email_verified_at'=>now()])->save();$this->actingAs($admin)->withSession(['auth.password_confirmed_at'=>now()->subHours(2)->timestamp])->get('/beheer/donaties')->assertRedirect('/beveiliging/bevestigen');
 }
 public function test_donations_use_individual_confirmed_payments_and_keep_test_mode_separate():void {
  $admin=$this->member(['is_admin'=>true]);$donor=$this->member(['name'=>'Vriendelijke gever']);$p=$this->pledge($donor,['frequency'=>'monthly','subscription_status'=>'active','subscription_id'=>'sub_private']);
  $this->payment($p,['refunded_cents'=>100]);$this->payment($p,['charged_back_cents'=>50]);$this->payment($this->pledge(null,['cents'=>2500]));
  $this->payment($p,['status'=>'failed','paid_at'=>null]);$this->payment($p,['status'=>'open','paid_at'=>null]);$this->payment($p,['paid_at'=>null]);
  $this->payment($this->pledge($donor,['mode'=>'test','cents'=>99999]));
  $r=$this->actingAs($admin)->get('/beheer/donaties?mode=live')->assertOk()->assertViewHas('totals',fn($t)=>(int)$t->gross===3500&&(int)$t->net===3350&&(int)$t->refunds===100&&(int)$t->chargebacks===50&&(int)$t->payments===3)->assertViewHas('activeCount',1)->assertViewHas('monthlyCents',500)->assertViewHas('pendingCount',1)->assertViewHas('failedCount',1)->assertSee('€ 33,50')->assertDontSee('999,99')->assertDontSee('tr_private')->assertDontSee('sub_private');$this->capture($r,'donations-filled');
  $this->get('/beheer/donaties?mode=test')->assertOk()->assertViewHas('totals',fn($t)=>(int)$t->net===99999)->assertViewHas('activeCount',0)->assertSee('Teststand')->assertSee('€ 999,99');
  $this->get('/beheer/bijdragen')->assertRedirect('/beheer/donaties?lang=nl');Http::assertNothingSent();
 }
 public function test_donation_dates_search_status_and_pagination_have_clear_scope():void {
  $this->travelTo(\Carbon\Carbon::parse('2026-10-23 12:00:00','UTC'));
  $admin=$this->member(['is_admin'=>true]);$u=$this->member(['name'=>'Zoekbare gever']);$p=$this->pledge($u);
  $this->payment($p,['paid_at'=>'2026-10-01 00:15:00']);$this->payment($p,['paid_at'=>'2026-09-30 21:59:00']);$this->payment($p,['status'=>'failed','paid_at'=>null]);
  $this->actingAs($admin)->get('/beheer/donaties?mode=live&from=2026-10-01&to=2026-10-23&q=Zoekbare&status=failed')->assertOk()->assertViewHas('totals',fn($t)=>(int)$t->net===500)->assertViewHas('payments',fn($list)=>$list->total()===1)->assertViewHas('monthCents',500);
  for($i=0;$i<24;$i++)$this->payment($p);
  $this->get('/beheer/donaties?mode=live')->assertOk()->assertViewHas('payments',fn($list)=>$list->count()===20&&$list->total()===27);
  $this->get('/beheer/donaties?mode=live&page=2')->assertOk()->assertViewHas('payments',fn($list)=>$list->count()===7);
  $this->get('/beheer/donaties?mode=invalid')->assertSessionHasErrors('mode');$this->get('/beheer/donaties?from=2026-10-24&to=2026-10-01')->assertSessionHasErrors('to');
 }
 public function test_moment_queue_and_member_detail_never_show_private_moments():void {
  $admin=$this->member(['is_admin'=>true]);$u=$this->member(['name'=>'Lieve lezer']);$this->moment($u);$this->moment($u,['body'=>'PRIVATE_MOMENT_SECRET','visibility'=>'private']);
  $this->actingAs($admin);$r=$this->get('/beheer/momentjes')->assertOk()->assertSee('Een kopje koffie')->assertDontSee('PRIVATE_MOMENT_SECRET');$this->capture($r,'moments');
  foreach(['overview','photos','moments','comments','stories','activity','moderation','privacy','donations'] as $section){$r=$this->get('/beheer/gebruikers/'.$u->id.'?section='.$section)->assertOk()->assertDontSee('PRIVATE_MOMENT_SECRET');$this->capture($r,'member-'.$section);}
  $this->get('/beheer/gebruikers/'.$u->id.'?section=moments')->assertViewHas('publicMoments',fn($list)=>$list->total()===1);
 }
 public function test_data_requests_move_under_users_and_handling_does_not_delete_account():void {
  $u=$this->member();$id=DB::table('privacy_requests')->insertGetId(['user_id'=>$u->id,'kind'=>'deletion','message'=>'Een persoonlijke vraag over mijn account.','created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($this->member(['is_admin'=>true]));$r=$this->get('/beheer/gebruikers?filter=privacy')->assertOk()->assertSee('Een persoonlijke vraag');$this->capture($r,'requests');
  $this->post('/beheer/privacy/'.$id,[])->assertSessionHasErrors('handled');$this->assertNull(DB::table('privacy_requests')->where('id',$id)->value('resolved_at'));
  $this->post('/beheer/privacy/'.$id,['handled'=>1])->assertRedirect();$this->assertDatabaseHas('users',['id'=>$u->id]);
  $this->get('/beheer/gebruikers?filter=privacy')->assertDontSee('Een persoonlijke vraag');$this->get('/beheer/gebruikers?filter=privacy&handled=all')->assertSee('Een persoonlijke vraag');
  $this->get('/beheer/privacy')->assertRedirect('/beheer/gebruikers?filter=privacy&lang=nl');
 }
 public function test_all_workspaces_render_with_real_content_and_escaped_input():void {
  $admin=$this->member(['is_admin'=>true]);$u=$this->member(['name'=>'<script>BAD</script>']);$a=$this->article();Comment::create(['user_id'=>$u->id,'article_id'=>$a->id,'body'=>'Wat een fijne ontwikkeling!','status'=>'doubt']);
  ProfilePhoto::create(['user_id'=>$u->id,'path'=>null,'status'=>'doubt','reason'=>'Even handmatig kijken.','self_confirmed_at'=>now()]);
  $this->actingAs($admin);foreach(['reacties','artikelen','lichtpuntjes','profielfotos','gebruikers','donaties'] as $part){$r=$this->get('/beheer/'.$part)->assertOk()->assertDontSee('<script>BAD</script>',false);$this->capture($r,$part);}
  $this->article(['status'=>'error','nl'=>['title'=>'Importfout voor controle']]);$this->get('/beheer/artikelen?status=error')->assertOk()->assertSee('Importfout voor controle');
  $a->update(['hidden_at'=>now()]);$this->get('/beheer/reacties')->assertOk()->assertSee('Artikel niet meer op de site')->assertDontSee('href="/?story=',false);
  Http::assertNothingSent();Queue::assertNothingPushed();Notification::assertNothingSent();
 }
 public function test_user_donations_filter_and_to_only_dates_work_without_mixing_accounts():void {
  $admin=$this->member(['is_admin'=>true]);$a=$this->member();$b=$this->member();$p=$this->pledge($a,['frequency'=>'monthly','subscription_status'=>'active']);$this->payment($p);
  $this->payment($this->pledge($b,['cents'=>9000,'frequency'=>'monthly','subscription_status'=>'active']));
  $this->actingAs($admin)->get('/beheer/donaties?user_id='.$a->id.'&to='.today('Europe/Amsterdam')->format('Y-m-d'))->assertOk()->assertViewHas('totals',fn($t)=>(int)$t->net===500)->assertViewHas('activeCount',1)->assertViewHas('subscriptions',fn($s)=>$s->total()===1);
  $this->get('/beheer/donaties?status=refunded')->assertOk()->assertViewHas('payments',fn($p)=>$p->total()===0);
 }
 public function test_comment_context_and_suspension_are_logged_and_sessions_revoked():void {
  $admin=$this->member(['is_admin'=>true]);$u=$this->member();$a=$this->article();$root=Comment::create(['user_id'=>$u->id,'article_id'=>$a->id,'body'=>'De oorspronkelijke fijne reactie.','status'=>'approved']);
  Comment::create(['user_id'=>$admin->id,'article_id'=>$a->id,'parent_id'=>$root->id,'thread_root_id'=>$root->id,'body'=>'Een antwoord binnen hetzelfde gesprek.','status'=>'doubt']);
  $this->actingAs($admin)->get('/beheer/reacties')->assertOk()->assertSee('In reactie op')->assertSee('De oorspronkelijke fijne reactie.')->assertSee('Een antwoord binnen hetzelfde gesprek.');
  $this->post('/beheer/users/'.$u->id,['suspended'=>1])->assertSessionHasErrors('note');
  $this->post('/beheer/users/'.$u->id,['suspended'=>1,'note'=>'Een reden voor de schorsing.'])->assertRedirect();$this->assertSame(1,(int)$u->fresh()->security_version);$this->assertTrue($u->fresh()->is_suspended);$this->assertDatabaseHas('user_moderation_events',['user_id'=>$u->id,'moderator_id'=>$admin->id,'note'=>'Een reden voor de schorsing.']);
  $this->post('/beheer/users/'.$admin->id,['suspended'=>1,'note'=>'Dit mag niet.'])->assertUnprocessable();
  $this->actingAs($u->fresh())->withSession(['account_security_version'=>0])->get('/profiel')->assertRedirect('/inloggen');
 }
}
