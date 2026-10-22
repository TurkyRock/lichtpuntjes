<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,Article,PositiveMoment,ProfilePhoto};
use App\Services\CommunityDemo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Storage,Http,Hash};
class CommunityDemoTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();Storage::fake('local');Http::preventStrayRequests();config(['cache.default'=>'array']);}
 protected function tearDown():void {try{if(app()->environment('testing'))app(CommunityDemo::class)->remove();}catch(\Throwable $e){}parent::tearDown();}
 private function realArticle():Article{return Article::create(['fingerprint'=>hash('sha256','real-kept-news'),'status'=>'published','origin'=>'news','category'=>'nature','source_title'=>'Bestaand nieuws','source_name'=>'Een echte bron','source_url'=>'https://example.com/news','published_at'=>now()->subDay(),'views_count'=>7,'likes_count'=>2,'nl'=>['title'=>'Bestaand nieuws','teaser'=>'Een mooie ontwikkeling','body'=>['Een mooi verhaal.']],'en'=>['title'=>'Existing news','teaser'=>'A lovely development','body'=>['A lovely story.']]]);}
 public function test_creates_four_complete_profiles_and_is_idempotent_without_external_ai_calls():void {
  $real=$this->realArticle();$d=app(CommunityDemo::class);$result=$d->install();
  $this->assertSame(4,$result['users']);$this->assertCount(4,$result['credentials']);
  $this->assertDatabaseCount('users',4);$this->assertDatabaseCount('articles',5);$this->assertDatabaseCount('lightspot_submissions',4);$this->assertDatabaseCount('comments',8);$this->assertDatabaseCount('profile_photos',4);
  $this->assertSame(4,PositiveMoment::where('visibility','public')->count());$this->assertGreaterThan(4,PositiveMoment::where('visibility','private')->count());
  foreach(User::all() as $u){$this->assertFalse($u->is_admin);$this->assertTrue($u->hasVerifiedEmail());$this->assertStringContainsString('(voorbeeld)',$u->name);$this->assertNotNull($u->avatarUrl());$this->assertGreaterThan(0,DB::table('lightpoint_events')->where('user_id',$u->id)->sum('points'));$this->assertGreaterThan(0,DB::table('user_article_likes')->where('user_id',$u->id)->count());$this->get($u->avatarUrl())->assertOk()->assertHeader('Content-Type','image/jpeg');}
  foreach($result['credentials'] as $c)$this->assertTrue(Hash::check($c['password'],User::where('email',$c['email'])->firstOrFail()->password));
  $counts=[];foreach(['users','articles','positive_moments','profile_photos','comments','lightpoint_events','article_reads','user_article_likes','moment_hearts'] as $table)$counts[$table]=DB::table($table)->count();
  $again=$d->install();$this->assertSame([],$again['credentials']);foreach($counts as $table=>$count)$this->assertSame($count,DB::table($table)->count(),$table);
  $response=$this->get('/')->assertOk();$this->assertCount(4,$response->viewData('momentHighlights'));$this->assertCount(4,$response->viewData('momentHighlights')->pluck('user_id')->unique());$response->assertSee('Lees het hele momentje');
  $this->actingAs(User::first())->get('/profiel?section=points')->assertOk()->assertSee('Voorbeeld: een lichtpuntje gelezen');
  Http::assertNothingSent();
 }
 public function test_removal_restores_existing_news_counters_and_keeps_other_users():void {
  $real=$this->realArticle();$user=User::create(['name'=>'Bestaande gebruiker','email'=>'existing@example.test','password'=>'SomethingLong123']);
  $d=app(CommunityDemo::class);$d->install();$this->assertGreaterThanOrEqual(7,$real->fresh()->views_count);
  $this->assertSame(4,$d->remove());$this->assertDatabaseCount('users',1);$this->assertDatabaseHas('users',['id'=>$user->id]);$this->assertDatabaseCount('articles',1);$this->assertSame(7,$real->fresh()->views_count);$this->assertSame(2,$real->fresh()->likes_count);$this->assertDatabaseCount('positive_moments',0);$this->assertDatabaseCount('article_reads',0);$this->assertDatabaseCount('user_article_likes',0);$this->assertDatabaseCount('moment_hearts',0);$this->assertDatabaseCount('profile_photos',0);$this->assertSame(0,$d->remove());
 }
 public function test_existing_account_collision_is_never_overwritten():void {
  $u=User::forceCreate(['name'=>'Beheerder','email'=>'sanne@demo.jouwlichtpuntje.invalid','password'=>'NeverChange123','is_admin'=>true]);$password=$u->password;
  try{app(CommunityDemo::class)->install();$this->fail('Expected refusal');}catch(\RuntimeException $e){$this->assertStringContainsString('al in gebruik',$e->getMessage());}
  $this->assertDatabaseCount('users',1);$this->assertSame($password,$u->fresh()->password);$this->assertTrue($u->fresh()->is_admin);$this->assertDatabaseCount('articles',0);
 }
 public function test_demo_command_refuses_production():void {
  app()->detectEnvironment(fn()=>'production');try{$this->artisan('lichtpunt:voorbeeldgebruikers')->expectsOutputToContain('uitsluitend bedoeld')->assertFailed();$this->assertDatabaseCount('users',0);}finally{app()->detectEnvironment(fn()=>'testing');}
 }
}
