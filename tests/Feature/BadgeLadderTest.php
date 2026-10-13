<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\Article;
use App\Models\Comment;
use App\Services\BadgeCatalog;
use App\Services\Lightpoints;
use App\Services\GeminiJson;
use App\Services\CommentModerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;
class BadgeLadderTest extends TestCase {
 protected function setUp():void {parent::setUp();app()->setLocale('nl');config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);DB::purge('sqlite');$this->artisan('migrate',['--force'=>true])->assertExitCode(0);Notification::fake();}
 private function user():User {$u=User::create(['name'=>'Zonnetje','email'=>Str::uuid().'@example.com','password'=>'SunnyPassword123']);$u->forceFill(['email_verified_at'=>now(),'community_accepted_at'=>now(),'community_version'=>'2026-10-10','invite_code'=>bin2hex(random_bytes(16))])->save();return $u;}
 private function article():Article {return Article::create(['fingerprint'=>hash('sha256',(string)Str::uuid()),'source_title'=>'Goed nieuws','source_name'=>'Bron','source_url'=>'https://example.com/news','status'=>'published','category'=>'people','published_at'=>now(),'nl'=>['title'=>'Goed nieuws','teaser'=>'Mooi','body'=>['Mooi nieuws']],'en'=>['title'=>'Good news','teaser'=>'Nice','body'=>['Good news']]]);}
 private function ai(string $decision):void {$this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->andReturn(['decision'=>$decision,'confidence'=>.99,'reason'=>'Vriendelijke reactie.']));}
 public function test_sixteen_assets_and_homepage_share_the_catalog():void {
  $stages=BadgeCatalog::stages('nl');$this->assertCount(16,$stages);$this->assertCount(16,array_unique(array_column($stages,'id')));
  foreach($stages as $b)$this->assertFileExists(public_path(ltrim($b['icon'],'/')));
  $this->assertSame([5,15,150],array_column(BadgeCatalog::homePreview(),'points'));
  $response=$this->get('/')->assertOk();foreach(BadgeCatalog::homePreview() as $b)$response->assertSee($b['icon'])->assertSee($b['name']);
 }
 public function test_all_thresholds_and_final_progress():void {
  $u=$this->user();$p=app(Lightpoints::class);$last=0;
  foreach(BadgeCatalog::stages() as $i=>$b){
   if($b['points']>$last)$p->award($u,'boundary:'.$i,'test',$b['points']-$last,'Test');
   $s=$p->summary($u);$this->assertSame($b['id'],$s['current']['id']);$this->assertSame($i+1,$s['earned_count']);$this->assertSame(16,$s['total_badges']);$last=$b['points'];
  }
  $this->assertNull($s['next']);$this->assertSame(100,$s['progress']);
 }
 public function test_profile_displays_ladder_and_private_rejection_reason():void {
  $u=$this->user();$c=Comment::create(['user_id'=>$u->id,'article_id'=>$this->article()->id,'body'=>'Oude reactie','status'=>'rejected','ai_reason'=>'Een persoonlijke aanval hoort hier niet.']);
  $this->actingAs($u)->get('/profiel')->assertOk()->assertSee('Lichtpuntenladder')->assertSee('Een persoonlijke aanval hoort hier niet.')->assertSee('/comments/'.$c->id.'/revise');
  $this->actingAs($this->user())->get('/profiel')->assertOk()->assertDontSee('Een persoonlijke aanval hoort hier niet.');
  $this->getJson('/news/'.$c->article_id.'/comments')->assertOk()->assertDontSee('Een persoonlijke aanval hoort hier niet.');
 }
 public function test_author_can_revise_but_others_cannot():void {
  $u=$this->user();$c=Comment::create(['user_id'=>$u->id,'article_id'=>$this->article()->id,'body'=>'Oude reactie','status'=>'rejected','ai_reason'=>'Negatief']);
  $this->actingAs($this->user())->post('/comments/'.$c->id.'/revise',['body'=>'Wat mooi!'])->assertForbidden();
  $this->ai('approve');$this->actingAs($u)->post('/comments/'.$c->id.'/revise',['body'=>'Wat mooi!'])->assertRedirect('/profiel');
  $this->assertSame('approved',$c->fresh()->status);$this->assertSame('Wat mooi!',$c->fresh()->body);
  $this->assertSame(1,app(Lightpoints::class)->summary($u)['points']);$this->assertDatabaseCount('comments',1);$this->assertDatabaseCount('comment_reviews',1);
 }
 public function test_resubmission_does_not_bypass_review_or_duplicate_points():void {
  $u=$this->user();$c=Comment::create(['user_id'=>$u->id,'article_id'=>$this->article()->id,'body'=>'Wat mooi!','status'=>'approved']);$p=app(Lightpoints::class);$p->comment($c);
  app(CommentModerator::class)->decide($c,'rejected',$this->user(),'Bij nader inzien niet positief');$this->ai('approve');
  $this->actingAs($u)->post('/comments/'.$c->id.'/revise',['body'=>'Wat een mooie prestatie!'])->assertRedirect('/profiel');
  $this->assertDatabaseCount('lightpoint_events',1);$this->assertSame(1,$p->summary($u)['points']);
  $this->post('/comments/'.$c->id.'/revise',['body'=>'Nog een versie!'])->assertUnprocessable();
 }
 public function test_unchanged_text_is_not_sent_to_ai_and_ai_rejection_stays_private():void {
  $u=$this->user();$c=Comment::create(['user_id'=>$u->id,'article_id'=>$this->article()->id,'body'=>'Oude reactie','status'=>'rejected']);
  $this->actingAs($u)->post('/comments/'.$c->id.'/revise',['body'=>'Oude reactie'])->assertSessionHasErrors('body');
  $this->ai('reject');$this->post('/comments/'.$c->id.'/revise',['body'=>'Andere reactie'])->assertRedirect('/profiel');
  $this->assertSame('rejected',$c->fresh()->status);$this->assertSame(0,app(Lightpoints::class)->summary($u)['points']);
 }
 public function test_english_profile_uses_the_same_assets_and_localised_titles():void {
  $this->actingAs($this->user())->get('/profiel?lang=en')->assertOk()->assertSee('The bright points ladder')->assertSee('/images/badges/smile.svg')->assertSee('Smile starter');
 }
 public function test_demo_account_is_local_only_idempotent_and_not_admin():void {
  $this->article();$this->app->instance('env','production');$this->artisan('lichtpunt:demo-account')->assertExitCode(1);$this->assertDatabaseCount('users',0);
  $this->app->instance('env','local');$this->artisan('lichtpunt:demo-account')->assertExitCode(0);$this->artisan('lichtpunt:demo-account')->assertExitCode(0);
  $u=User::where('email','erik.demo@lichtpunt.test')->firstOrFail();$this->assertFalse($u->is_admin);$this->assertTrue($u->hasVerifiedEmail());$this->assertSame(115,app(Lightpoints::class)->summary($u)['points']);$this->assertDatabaseCount('users',1);$this->assertDatabaseCount('comments',2);$this->assertDatabaseMissing('comments',['user_id'=>$u->id,'status'=>'approved']);
  $this->actingAs($u)->get('/profiel')->assertOk()->assertSee('Demokeuring');
 }
}
