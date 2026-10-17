<?php
namespace Tests\Feature;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Services\GeminiJson;
use App\Services\CommentModerator;
use App\Services\Lightpoints;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;
class CommunityTest extends TestCase {
 protected function setUp():void {parent::setUp();app()->setLocale('nl');config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);DB::purge('sqlite');$this->artisan('migrate',['--force'=>true])->assertExitCode(0);}
 private function user(array $extra=[]):User {$u=User::create(['name'=>'Zonnetje','email'=>Str::uuid().'@example.com','password'=>'SunnyPassword123']);$u->forceFill(array_merge(['email_verified_at'=>now(),'community_accepted_at'=>now(),'community_version'=>'2026-10-10','invite_code'=>bin2hex(random_bytes(16))],$extra))->save();return $u;}
 private function article():Article {return Article::create(['fingerprint'=>hash('sha256',(string)Str::uuid()),'source_title'=>'Een mooie overwinning','source_name'=>'Testbron','source_url'=>'https://example.com/news','status'=>'published','category'=>'sport','published_at'=>now(),'nl'=>['title'=>'Goed','teaser'=>'Mooi','body'=>['Mooi nieuws']],'en'=>['title'=>'Good','teaser'=>'Nice','body'=>['Happy news']]]);}
 private function aiResult(string $decision,float $confidence=.99):void {$this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->andReturn(['decision'=>$decision,'confidence'=>$confidence,'reason'=>'Testkeuring']));}
 public function test_guests_cannot_like_or_comment_and_members_cannot_access_admin():void {$a=$this->article();$this->postJson('/news/'.$a->id.'/like',['liked'=>true])->assertUnauthorized();$this->postJson('/news/'.$a->id.'/comments',['body'=>'Wat mooi!'])->assertUnauthorized();$this->actingAs($this->user())->get('/beheer')->assertForbidden();}
 public function test_member_account_navigation_keeps_administration_private():void {
  $this->get('/profiel')->assertRedirect();
  $this->actingAs($this->user())->get('/profiel?lang=nl')->assertOk()
   ->assertSee('class="account-sidebar"',false)
   ->assertSee('/profiel?lang=nl&amp;section=badges',false)
   ->assertSee('/profiel?lang=nl&amp;section=comments',false)
   ->assertSee('data-account-section="overview"',false)
   ->assertDontSee('id="inviteTitle"',false)
   ->assertDontSee('class="account-admin-menu"',false)
   ->assertDontSee('href="/beheer',false);
  foreach(['/beheer','/beheer/artikelen','/beheer/lichtpuntjes','/beheer/privacy'] as $adminPath)$this->get($adminPath)->assertForbidden();
 }
 public function test_admin_has_both_account_and_administration_navigation():void {
  $this->actingAs($this->user(['is_admin'=>true]));
  foreach(['/profiel','/beheer'] as $accountPath)$this->get($accountPath.'?lang=nl')->assertOk()
   ->assertSee('class="account-sidebar"',false)
   ->assertSee('class="account-admin-menu"',false)
   ->assertSee('href="/mijn-lichtpuntjes?lang=nl"',false)
   ->assertSee('href="/beheer/artikelen?lang=nl"',false);
 }
 public function test_english_account_navigation_and_unverified_account_are_available():void {
  $this->actingAs($this->user(['email_verified_at'=>null]))->get('/profiel?lang=en')->assertOk()
   ->assertSee('Account sections')
   ->assertSee('Confirm your email address')
   ->assertSee('/profiel?lang=en&amp;section=invite',false)
   ->assertDontSee('class="account-admin-menu"',false);
 }
 public function test_account_sections_render_only_the_selected_content_and_active_link():void {
  $this->actingAs($this->user());
  foreach(['overview','badges','comments','invite','points'] as $section){
   $response=$this->get('/profiel?lang=nl&section='.$section)->assertOk()->assertSee('data-account-section="'.$section.'"',false);
   $this->assertMatchesRegularExpression('/href="\/profiel\?lang=nl&amp;section='.$section.'"\s+aria-current="page"/',$response->getContent());
   foreach(['badges'=>'id="ladderTitle"','comments'=>'id="myCommentsTitle"','invite'=>'id="inviteTitle"'] as $otherSection=>$marker){
    if($section===$otherSection)$response->assertSee($marker,false);else $response->assertDontSee($marker,false);
   }
  }
  $this->get('/profiel?section=unknown')->assertOk()->assertSee('data-account-section="overview"',false);
  $this->get('/profiel?section[]=comments')->assertOk()->assertSee('data-account-section="overview"',false);
 }
 public function test_registration_requires_rules_and_cannot_grant_admin():void {Notification::fake();$data=['name'=>'Vriend','email'=>'friend@example.com','password'=>'SunnyPassword123','password_confirmation'=>'SunnyPassword123','is_admin'=>true];$this->postJson('/aanmelden',$data)->assertUnprocessable()->assertJsonValidationErrors('intentions');$this->post('/aanmelden',$data+['intentions'=>1])->assertRedirect('/profiel');$this->assertFalse(User::where('email','friend@example.com')->firstOrFail()->is_admin);}
 public function test_unverified_account_cannot_comment():void {$a=$this->article();$this->actingAs($this->user(['email_verified_at'=>null]))->postJson('/news/'.$a->id.'/comments',['body'=>'Wat mooi!'])->assertForbidden();$this->assertDatabaseCount('comments',0);}
 public function test_positive_comment_is_public_and_rewarded_exactly_once():void {$u=$this->user();$a=$this->article();$this->aiResult('approve');$this->actingAs($u)->postJson('/news/'.$a->id.'/comments',['body'=>'Wat een mooie prestatie!'])->assertCreated()->assertJsonPath('status','approved');$c=Comment::firstOrFail();app(CommentModerator::class)->review($c);$this->assertSame(1,app(Lightpoints::class)->summary($u)['points']);$this->getJson('/news/'.$a->id.'/comments')->assertJsonPath('total',1);}
 public function test_rejection_and_low_confidence_are_never_public():void {$u=$this->user();$a=$this->article();$this->aiResult('approve',.50);$this->actingAs($u)->postJson('/news/'.$a->id.'/comments',['body'=>'Wat bedoel je daar mee?'])->assertCreated()->assertJsonPath('status','doubt');$this->getJson('/news/'.$a->id.'/comments')->assertJsonPath('total',0);$this->assertSame(0,app(Lightpoints::class)->summary($u)['points']);}
 public function test_ai_outage_goes_to_doubt_without_points():void {$u=$this->user();$a=$this->article();$this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->andThrow(new \RuntimeException('Offline')));$this->actingAs($u)->postJson('/news/'.$a->id.'/comments',['body'=>'Wat mooi!'])->assertCreated()->assertJsonPath('status','doubt');$this->assertTrue(Comment::first()->ai_error);$this->assertSame(0,app(Lightpoints::class)->summary($u)['points']);}
 public function test_only_author_sees_rejected_comment_and_can_appeal_once():void {$u=$this->user();$a=$this->article();$this->aiResult('reject');$this->actingAs($u)->postJson('/news/'.$a->id.'/comments',['body'=>'Dit is een testreactie.'])->assertJsonPath('status','rejected');$c=Comment::first();$this->getJson('/news/'.$a->id.'/comments')->assertJsonPath('own.0.status','rejected');$this->actingAs($this->user())->getJson('/news/'.$a->id.'/comments')->assertJsonPath('own',[]);$this->post('/comments/'.$c->id.'/appeal')->assertForbidden();$this->actingAs($u)->post('/comments/'.$c->id.'/appeal')->assertRedirect();$this->post('/comments/'.$c->id.'/appeal')->assertUnprocessable();}
 public function test_admin_can_approve_revoke_and_restore_without_duplicate_points():void {$u=$this->user();$admin=$this->user(['is_admin'=>true]);$c=Comment::create(['user_id'=>$u->id,'article_id'=>$this->article()->id,'body'=>'Wat mooi!','status'=>'doubt']);$m=app(CommentModerator::class);$m->decide($c,'approved',$admin,'Positieve reactie');$m->decide($c,'approved',$admin,'Nog steeds goed');$this->assertSame(1,app(Lightpoints::class)->summary($u)['points']);$m->decide($c,'rejected',$admin,'Toch niet passend');$this->assertSame(0,app(Lightpoints::class)->summary($u)['points']);$m->decide($c,'approved',$admin,'Bij nader inzien goed');$this->assertSame(1,app(Lightpoints::class)->summary($u)['points']);$this->assertDatabaseCount('lightpoint_events',1);$this->assertDatabaseCount('comment_reviews',4);}
 public function test_daily_read_and_share_limits_are_server_enforced():void {$u=$this->user();$this->actingAs($u);for($i=0;$i<6;$i++){$a=$this->article();$this->postJson('/news/'.$a->id.'/read')->assertOk();$this->postJson('/news/'.$a->id.'/share')->assertOk();$this->postJson('/news/'.$a->id.'/share')->assertOk()->assertJsonPath('awarded',false);}$this->assertSame(8,app(Lightpoints::class)->summary($u)['points']);}
 public function test_paused_account_cannot_like_or_comment():void {$a=$this->article();$this->actingAs($this->user(['is_suspended'=>true]))->postJson('/news/'.$a->id.'/like',['liked'=>true])->assertForbidden();$this->postJson('/news/'.$a->id.'/comments',['body'=>'Wat mooi!'])->assertForbidden();}
 public function test_owner_command_assigns_only_one_admin():void {$first=$this->user(['is_admin'=>true]);$second=$this->user();$this->artisan('lichtpunt:admin',['email'=>$second->email])->assertExitCode(0);$this->assertFalse($first->fresh()->is_admin);$this->assertTrue($second->fresh()->is_admin);}
 public function test_badge_thresholds_and_private_account_data():void {$u=$this->user();$p=app(Lightpoints::class);$p->award($u,'test:first','test',75,'Testpunten');$this->assertSame('Wandelend zonnetje',$p->summary($u)['current']['name']);$this->actingAs($u)->get('/profiel')->assertOk()->assertSee('Wandelend zonnetje');$this->assertArrayNotHasKey('email',$u->toArray());}
 public function test_admin_page_renders_empty_and_appealed_comments():void {$admin=$this->user(['is_admin'=>true]);$this->actingAs($admin)->get('/beheer')->assertOk();Comment::create(['user_id'=>$this->user()->id,'article_id'=>$this->article()->id,'body'=>'Wat mooi!','status'=>'doubt','appealed_at'=>now()]);$this->get('/beheer')->assertOk()->assertSee('Menselijke blik aangevraagd');}
}
