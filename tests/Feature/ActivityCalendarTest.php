<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\{User,Article,Comment,PositiveMoment};
use App\Services\{ActivityCalendar,Lightpoints};
use Carbon\{Carbon,CarbonImmutable};
class ActivityCalendarTest extends TestCase {
 use RefreshDatabase;
 private function member(array $v=[]):User{return User::forceCreate($v+['name'=>'Vrolijke bezoeker','email'=>bin2hex(random_bytes(7)).'@example.test','password'=>'longTestPassword1234','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 private function article():Article{return Article::create(['fingerprint'=>bin2hex(random_bytes(24)),'source_title'=>'Een fijn verhaal','source_name'=>'Bron','source_url'=>'https://example.com/news','category'=>'people','status'=>'published','published_at'=>now(),'nl'=>['title'=>'Een fijn verhaal','teaser'=>'Mooi nieuws','body'=>['Een fijne dag.']],'en'=>['title'=>'A lovely story','teaser'=>'Good news','body'=>['A lovely day.']]]);}
 public function test_second_visit_day_earns_one_point_after_amsterdam_midnight_without_streak():void {
  $this->travelTo(Carbon::parse('2026-10-17 23:59:00','Europe/Amsterdam'));$u=$this->member();$this->actingAs($u)->get('/')->assertOk();$this->get('/profiel')->assertOk()->assertSee('Mijn kalender');
  $this->assertDatabaseCount('user_visits',1);$this->assertSame(0,app(Lightpoints::class)->summary($u)['points']);
  $this->travel(2)->minutes();$this->get('/profiel')->assertOk()->assertSee('lichtpuntjes erbij');$this->get('/')->assertOk();
  $this->assertDatabaseCount('user_visits',2);$this->assertSame(1,app(Lightpoints::class)->summary($u)['points']);
  $this->postJson('/lichtpuntjes/meldingen')->assertOk()->assertJsonPath('notice.points',1)->assertJsonPath('notice.label','Fijn dat je er weer bent!');
  $this->travel(4)->days();$this->get('/')->assertOk();$this->assertSame(2,app(Lightpoints::class)->summary($u)['points']);$this->assertDatabaseCount('user_visits',3);
 }
 public function test_json_polling_guests_and_ineligible_accounts_do_not_record_visits():void {
  $this->get('/')->assertOk();$u=$this->member();$this->actingAs($u)->getJson('/news/feed')->assertOk();$this->postJson('/lichtpuntjes/meldingen')->assertOk();$this->assertDatabaseCount('user_visits',0);
  foreach([['is_suspended'=>true],['email_verified_at'=>null],['community_accepted_at'=>null]] as $v){$u=$this->member($v);app(ActivityCalendar::class)->visit($u);}
  $this->assertDatabaseCount('user_visits',0);$this->assertDatabaseCount('lightpoint_events',0);
 }
 public function test_read_activity_includes_over_cap_actions_without_duplicate_rewards():void {
  $u=$this->member();$this->actingAs($u);for($i=0;$i<7;$i++){$a=$this->article();$this->postJson('/news/'.$a->id.'/read')->assertOk();}
  $this->postJson('/news/'.$a->id.'/read')->assertOk();$this->assertSame(5,app(Lightpoints::class)->summary($u)['points']);$this->assertDatabaseCount('user_activities',7);
  $cal=app(ActivityCalendar::class)->month($u,CarbonImmutable::now('Europe/Amsterdam')->startOfMonth(),now('Europe/Amsterdam')->toDateString());
  $this->assertSame(7,$cal['detail']['activities']['read']);$this->assertSame(5,$cal['detail']['points']);$this->assertSame(0,$cal['visits']);$this->assertSame(1,$cal['active_days']);
 }
 public function test_private_month_view_shows_only_own_approved_activity_and_preserves_legacy_history():void {
  $this->travelTo(Carbon::parse('2026-10-17 12:00','Europe/Amsterdam'));$u=$this->member();$other=$this->member();
  foreach([[$u,'private','approved'],[$other,'private','approved'],[$u,'private','pending']] as [$owner,$visibility,$status])PositiveMoment::create(['user_id'=>$owner->id,'visibility'=>$visibility,'status'=>$status,'body'=>$owner->id===$u->id?'OWN_SECRET_MOMENT':'OTHER_SECRET_MOMENT','moment_date'=>'2026-09-05','ai_accepted_at'=>now()]);
  DB::table('user_visits')->insert(['user_id'=>$other->id,'visit_date'=>'2026-09-05']);
  app(Lightpoints::class)->award($u,'read:2026-09-05:111','read',1,'Historisch lezen');DB::table('lightpoint_events')->where('user_id',$u->id)->update(['period'=>'2026-09-05']);DB::table('user_activities')->where('user_id',$u->id)->delete();
  $cal=app(ActivityCalendar::class)->month($u,CarbonImmutable::parse('2026-09-01','Europe/Amsterdam'),'2026-09-05');
  $this->assertSame(1,$cal['detail']['activities']['moment']);$this->assertSame(1,$cal['detail']['activities']['read']);$this->assertSame(0,$cal['visits']);
  $this->actingAs($u)->get('/profiel?month=2026-09&day=2026-09-05')->assertOk()->assertSee('5 september')->assertSee('Goedgekeurde momentjes bewaard')->assertDontSee('OTHER_SECRET_MOMENT');
  $this->get('/profiel?lang=en&month=2026-09&day=2026-09-05')->assertOk()->assertSee('My calendar')->assertSee('Approved moments saved')->assertSee('Only you can see this overview');
  $this->get('/profiel?month=invalid')->assertSessionHasErrors('month');$this->get('/profiel?month=2026-09&day=2026-10-01')->assertUnprocessable();
 }
 public function test_calendar_counts_comments_stories_shares_and_hearts_with_correct_points():void {
  $this->travelTo(Carbon::parse('2026-10-17 10:00','Europe/Amsterdam'));$u=$this->member();$a=$this->article();$a->update(['origin'=>'community','author_id'=>$u->id]);
  Comment::create(['article_id'=>$a->id,'user_id'=>$u->id,'body'=>'Wat een fijn verhaal.','status'=>'approved']);
  $p=app(Lightpoints::class);$p->award($u,'story:'.$a->id,'story',5,'Eigen verhaal');$p->award($u,'comment:1','comment',1,'Vriendelijke reactie');$p->award($u,'share:'.now('Europe/Amsterdam')->toDateString().':'.$a->id,'share',1,'Delen');$p->award($u,'heart:moment:'.now('Europe/Amsterdam')->toDateString().':123','heart',1,'Liefde');
  $cal=app(ActivityCalendar::class)->month($u,CarbonImmutable::now('Europe/Amsterdam')->startOfMonth(),now('Europe/Amsterdam')->toDateString());
  foreach(['comment','story','share','heart'] as $kind)$this->assertSame(1,$cal['detail']['activities'][$kind]);$this->assertSame(8,$cal['detail']['points']);$this->assertSame(5,$cal['detail']['points_by_kind']['story']);
 }
 public function test_deleting_account_cascades_visit_and_activity_records():void {
  $u=$this->member();app(ActivityCalendar::class)->visit($u);app(ActivityCalendar::class)->record($u,'read','read:today:1');$this->assertDatabaseCount('user_visits',1);$this->assertDatabaseCount('user_activities',1);$u->delete();$this->assertDatabaseCount('user_visits',0);$this->assertDatabaseCount('user_activities',0);
 }
 public function test_legacy_hearts_appear_only_in_overview_calendar_without_duplicate_counts():void {
  $this->travelTo(Carbon::parse('2026-10-17 12:00','Europe/Amsterdam'));$u=$this->member();$owner=$this->member();$a=$this->article();
  DB::table('user_article_likes')->insert(['user_id'=>$u->id,'article_id'=>$a->id,'created_at'=>now()]);
  DB::table('user_article_likes')->insert(['user_id'=>$owner->id,'article_id'=>$a->id,'created_at'=>now()]);
  $moment=PositiveMoment::create(['user_id'=>$owner->id,'body'=>'Een fijn kopje koffie.','status'=>'approved','visibility'=>'public','moment_date'=>'2026-10-17','ai_accepted_at'=>now()]);
  DB::table('moment_hearts')->insert(['user_id'=>$u->id,'moment_id'=>$moment->id,'created_at'=>now()]);
  $cal=app(ActivityCalendar::class)->month($u,CarbonImmutable::now('Europe/Amsterdam')->startOfMonth(),'2026-10-17');
  $this->assertSame(2,$cal['detail']['activities']['heart']);$this->assertSame(0,$cal['detail']['points']??0);$this->assertDatabaseCount('user_activities',0);$this->assertDatabaseCount('lightpoint_events',0);
  $this->actingAs($u)->get('/mijn-momentjes')->assertOk()->assertDontSee('id="activity-calendar"',false)->assertSee('Vorige maand');
  $this->get('/profiel')->assertOk()->assertSee('Hartjes gegeven')->assertSee('Vandaag')->assertSee('2026-10-17, vandaag');
  $this->postJson('/news/'.$a->id.'/like',['liked'=>true])->assertOk()->assertJsonPath('points_awarded',1);
  $cal=app(ActivityCalendar::class)->month($u,CarbonImmutable::now('Europe/Amsterdam')->startOfMonth(),'2026-10-17');
  $this->assertSame(2,$cal['detail']['activities']['heart']);$this->assertSame(1,$cal['detail']['points']);
  $this->get('/profiel?lang=en')->assertOk()->assertSee('Hearts given')->assertSee('Today');
 }
 public function test_legacy_like_uses_dutch_day_at_the_month_boundary():void {
  $this->travelTo(Carbon::parse('2026-10-17 12:00','Europe/Amsterdam'));$u=$this->member();$a=$this->article();
  DB::table('user_article_likes')->insert(['user_id'=>$u->id,'article_id'=>$a->id,'created_at'=>'2026-09-30 23:15:00']);
  $cal=app(ActivityCalendar::class)->month($u,CarbonImmutable::parse('2026-10-01','Europe/Amsterdam'),'2026-10-01');
  $this->assertSame(1,$cal['detail']['activities']['heart']);$this->assertArrayNotHasKey('2026-09-30',$cal['days']);
  $prior=app(ActivityCalendar::class)->month($u,CarbonImmutable::parse('2026-09-01','Europe/Amsterdam'),'2026-09-30');$this->assertSame([],$prior['detail']);
 }

}
