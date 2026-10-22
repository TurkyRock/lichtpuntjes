<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\PositiveMoment;
use App\Services\GeminiJson;
use App\Services\MomentModerator;
use App\Jobs\ReviewMoment;
class PositiveMomentsTest extends TestCase {
 use RefreshDatabase;
 private function user(array $v=[]):User {return User::forceCreate(array_merge(['name'=>'Momentenschrijver','email'=>bin2hex(random_bytes(5)).'@example.test','password'=>'LongTest123456','email_verified_at'=>now(),'community_accepted_at'=>now()],$v));}
 private function moment(User $u,array $v=[]):PositiveMoment{return PositiveMoment::create(array_merge(['user_id'=>$u->id,'body'=>'De buurvrouw bracht koffie.','visibility'=>'private','status'=>'pending','moment_date'=>now('Europe/Amsterdam')->toDateString(),'ai_accepted_at'=>now()],$v));}
 private function ai(string $decision='approve'):void {$this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->andReturn(['decision'=>$decision,'confidence'=>.99,'reason_nl'=>'Dank voor je momentje.','reason_en'=>'Thank you for your moment.']));}
 public function test_chooser_separates_moments_and_existing_story_and_has_no_private_data_for_guests():void {$this->get('/lichtpuntje/delen')->assertRedirect('/inloggen');$this->actingAs($this->user())->get('/lichtpuntje/delen')->assertOk()->assertSee('Een momentje')->assertSee('Een verhaal')->assertSee('/lichtpuntje/schrijven?lang=nl',false);$this->get('/lichtpuntje/delen?lang=en')->assertOk()->assertSee('A little moment');}
 public function test_private_creation_requires_ai_agreement_and_is_queued_encrypted():void {
  Queue::fake();$u=$this->user();$this->actingAs($u)->post('/momentjes',['body'=>'Een privézin die niet in de database leesbaar mag staan.','visibility'=>'private'])->assertSessionHasErrors('ai_processing');$this->post('/momentjes',['body'=>'Een privézin die niet in de database leesbaar mag staan.','visibility'=>'private','ai_processing'=>1])->assertRedirect();$m=PositiveMoment::firstOrFail();$this->assertSame('pending',$m->status);$this->assertStringNotContainsString('privézin',DB::table('positive_moments')->value('body'));Queue::assertPushed(ReviewMoment::class);$this->get('/momentjes')->assertDontSee('Een privézin');
 }
 public function test_private_and_unapproved_moments_never_leak_into_public_feed_or_admin_detail():void {
  $u=$this->user();$this->moment($u,['body'=>'MIJN_GEHEIME_MOMENT','status'=>'approved']);$this->moment($u,['body'=>'NIET_GOEDGEKEURD','visibility'=>'public','status'=>'rejected']);$this->moment($u,['body'=>'Een openbaar fijn moment.','visibility'=>'public','status'=>'approved']);$this->get('/momentjes')->assertOk()->assertSee('Een openbaar fijn moment.')->assertDontSee('MIJN_GEHEIME_MOMENT')->assertDontSee('NIET_GOEDGEKEURD');$this->actingAs($this->user(['is_admin'=>true]))->get('/beheer/gebruikers/'.$u->id)->assertOk()->assertDontSee('MIJN_GEHEIME_MOMENT')->assertSee('NIET_GOEDGEKEURD');
 }
 public function test_private_moments_are_sent_to_ai_but_explicit_vulgar_word_is_rejected_even_if_model_approves():void {
  $u=$this->user();$m=$this->moment($u,['body'=>'Ik had een kut dag.']);$this->ai();app(MomentModerator::class)->review($m,1);$this->assertSame('rejected',$m->fresh()->status);$this->assertSame(0,DB::table('lightpoint_events')->where('user_id',$u->id)->count());$this->actingAs($u)->get('/mijn-momentjes')->assertOk()->assertSee('Gebruik vriendelijker taalgebruik');
 }
 public function test_rough_day_without_profanity_is_allowed_privately_and_earns_only_one_point_per_date():void {
  $u=$this->user();$this->ai();$m=$this->moment($u,['body'=>'Ik had een rotdag.']);app(MomentModerator::class)->review($m,1);$second=$this->moment($u);app(MomentModerator::class)->review($second,1);$this->assertSame('approved',$m->fresh()->status);$this->assertSame(1,DB::table('lightpoint_events')->where('user_id',$u->id)->where('active',true)->sum('points'));
 }
 public function test_edit_rechecks_and_stale_jobs_cannot_publish_previous_version():void {
  Queue::fake();$u=$this->user();$m=$this->moment($u,['visibility'=>'public','status'=>'approved']);$this->actingAs($u)->put('/momentjes/'.$m->id,['body'=>'Nieuwe tekst voor mijn momentje.','visibility'=>'private','ai_processing'=>1])->assertRedirect();$this->assertSame(2,$m->fresh()->revision);$this->assertSame('pending',$m->fresh()->status);$this->mock(GeminiJson::class,fn($mock)=>$mock->shouldNotReceive('ask'));app(MomentModerator::class)->review($m,1);$this->assertSame('pending',$m->fresh()->status);$this->get('/momentjes')->assertDontSee('Nieuwe tekst voor mijn momentje.');
 }
 public function test_only_owner_can_edit_delete_or_see_private_moment_and_admin_cannot_moderate_it():void {
  $u=$this->user();$m=$this->moment($u,['body'=>'Een privémoment van iemand anders.']);$this->actingAs($this->user())->put('/momentjes/'.$m->id,['body'=>'Andere tekst.','visibility'=>'public','ai_processing'=>1])->assertForbidden();$this->delete('/momentjes/'.$m->id)->assertForbidden();$this->get('/mijn-momentjes')->assertDontSee('Een privémoment van iemand anders.');$this->actingAs($this->user(['is_admin'=>true]))->post('/beheer/momentjes/'.$m->id,['decision'=>'approved','note'=>'Test'])->assertNotFound();
 }
 public function test_ai_outage_keeps_moment_private_and_allows_retry():void {
  $u=$this->user();$m=$this->moment($u,['visibility'=>'public']);$this->mock(GeminiJson::class,fn($mock)=>$mock->shouldReceive('ask')->andThrow(new \RuntimeException('secret provider detail')));app(MomentModerator::class)->review($m,1);$this->assertSame('doubt',$m->fresh()->status);$this->get('/momentjes')->assertDontSee('De buurvrouw bracht koffie.');$this->actingAs($u)->get('/mijn-momentjes')->assertOk()->assertSee('opnieuw controleren')->assertDontSee('secret provider detail');
 }
 public function test_moment_list_keeps_month_navigation_and_day_filter_without_calendar():void {
  $u=$this->user();$this->moment($u,['status'=>'approved','moment_date'=>'2026-10-02']);$this->actingAs($u)->get('/mijn-momentjes?month=2026-10')->assertOk()->assertSee('Vorige maand')->assertSee('Volgende maand')->assertDontSee('id="activity-calendar"',false)->assertHeader('X-Robots-Tag','noindex');$this->get('/mijn-momentjes?month=2026-10&day=2026-10-02')->assertSee('De buurvrouw bracht koffie.');
 }
 public function test_evening_invitation_disappears_after_any_submission_today():void {
  $this->travelTo(\Carbon\Carbon::parse('2026-10-17 19:05:00','Europe/Amsterdam'));$u=$this->user();$this->actingAs($u)->get('/')->assertOk()->assertSee('Sluit je dag af met iets moois');$this->moment($u);$this->get('/')->assertDontSee('Sluit je dag af met iets moois');
 }
 public function test_public_moderation_and_suspension_hide_moment():void {
  $u=$this->user();$m=$this->moment($u,['visibility'=>'public','status'=>'doubt']);$admin=$this->user(['is_admin'=>true]);$this->actingAs($admin)->post('/beheer/momentjes/'.$m->id,['decision'=>'approved','note'=>'Vriendelijke bijdrage.'])->assertRedirect();$this->get('/momentjes')->assertSee('De buurvrouw bracht koffie.');$this->post('/beheer/users/'.$u->id,['suspended'=>1,'note'=>'Herhaaldelijk onvriendelijk.'])->assertRedirect();$this->get('/momentjes')->assertDontSee('De buurvrouw bracht koffie.');
 }
 public function test_removing_last_approved_moment_revokes_its_point():void {
  $u=$this->user();$m=$this->moment($u);$this->ai();app(MomentModerator::class)->review($m,1);$this->actingAs($u)->delete('/momentjes/'.$m->id)->assertRedirect();$this->assertDatabaseMissing('positive_moments',['id'=>$m->id]);$this->assertSame(0,DB::table('lightpoint_events')->where('user_id',$u->id)->where('active',true)->sum('points'));
 }

 public function test_hearts_require_login_public_approval_and_unique_member_vote():void {
  $owner=$this->user();$public=$this->moment($owner,['visibility'=>'public','status'=>'approved']);$private=$this->moment($owner,['status'=>'approved']);
  $this->post('/momentjes/'.$public->id.'/hart',['liked'=>1])->assertRedirect('/inloggen');$viewer=$this->user();$this->actingAs($viewer)->post('/momentjes/'.$private->id.'/hart',['liked'=>1])->assertNotFound();$this->post('/momentjes/'.$public->id.'/hart',['liked'=>1])->assertRedirect();$this->post('/momentjes/'.$public->id.'/hart',['liked'=>1])->assertRedirect();$this->assertDatabaseCount('moment_hearts',1);$this->get('/momentjes')->assertOk()->assertSee('Hartje intrekken');$this->post('/momentjes/'.$public->id.'/hart',['liked'=>0])->assertRedirect();$this->assertDatabaseCount('moment_hearts',0);
 }
 public function test_privatizing_shared_moment_hides_it_and_clears_previous_hearts():void {
  Queue::fake();$u=$this->user();$m=$this->moment($u,['visibility'=>'public','status'=>'approved']);$viewer=$this->user();$this->actingAs($viewer)->post('/momentjes/'.$m->id.'/hart',['liked'=>1])->assertRedirect();$this->actingAs($u)->put('/momentjes/'.$m->id,['body'=>'De buurvrouw bracht koffie.','visibility'=>'private','ai_processing'=>1])->assertRedirect();$this->get('/momentjes')->assertDontSee('De buurvrouw bracht koffie.');$this->assertDatabaseCount('moment_hearts',0);
 }
 public function test_account_preview_only_reads_own_approved_moments():void {
  $this->withoutExceptionHandling();
  $me=$this->user();$other=$this->user();
  $this->moment($me,['body'=>'OWN_PRIVATE_JOY','status'=>'approved']);
  $this->moment($me,['body'=>'OWN_PENDING_JOY','status'=>'pending']);
  $this->moment($other,['body'=>'OTHER_PRIVATE_JOY','status'=>'approved']);
  $this->actingAs($me)->get('/profiel')->assertOk()->assertSee('account-scrapbook')->assertSee('Mijn kalender')->assertSee('OWN_PRIVATE_JOY')->assertDontSee('OTHER_PRIVATE_JOY')->assertDontSee('OWN_PENDING_JOY');
  $this->get('/profiel?lang=en')->assertOk()->assertSee('My calendar');
 }
 public function test_all_scrapbook_account_pages_render_in_dutch_and_english():void {
  $this->actingAs($this->user());
  foreach(['nl','en'] as $lang){
   foreach(['/profiel','/profiel?section=badges','/profiel?section=comments','/profiel?section=invite','/profiel?section=points','/mijn-momentjes','/mijn-lichtpuntjes','/lichtpuntje/delen','/lichtpuntje/schrijven','/privacy/verzoeken'] as $path){
    $this->get($path.(str_contains($path,'?')?'&':'?').'lang='.$lang)->assertOk()->assertSee('class="account-scrapbook"',false);
   }
  }
 }
 public function test_long_public_moment_has_complete_safe_reading_content_and_short_preview():void {
  $u=$this->user();$body=str_repeat('Een kop koffie in de zon. ',8)."\nEen fijn einde.";
  $this->moment($u,['body'=>$body,'status'=>'approved','visibility'=>'public']);
  $html=view('homepage.moments',['momentHighlights'=>PositiveMoment::publicVisible()->with('user.profilePhoto')->get(),'locale'=>'nl','showMomentPrompt'=>false,'likedMomentIds'=>[]])->render();
  $this->assertStringContainsString('data-moment-open',$html);
  $this->assertStringContainsString('Lees het hele momentje',$html);
  $this->assertStringContainsString(e($body),$html);
  preg_match('/<p class="moment-quote">(.*?)<\/p>/s',$html,$match);
  $this->assertLessThanOrEqual(120,mb_strlen(html_entity_decode($match[1])));
  $this->assertStringContainsString('moment-clothespin',$html);
  $this->assertStringNotContainsString('moment-note-tape',$html);
 }
}
