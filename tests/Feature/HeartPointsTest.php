<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\{User,PositiveMoment,Article};
use App\Services\{Lightpoints,MomentModerator};
class HeartPointsTest extends TestCase {
 use RefreshDatabase;
 private function member(array $v=[]):User {return User::forceCreate($v+['name'=>'Vriendelijke lezer','email'=>bin2hex(random_bytes(6)).'@example.test','password'=>'testPassword12345','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 private function moment(User $u,array $v=[]):PositiveMoment{return PositiveMoment::create($v+['user_id'=>$u->id,'body'=>'Een kop koffie in de zon.','visibility'=>'public','status'=>'approved','moment_date'=>now('Europe/Amsterdam')->toDateString(),'ai_accepted_at'=>now()]);}
 private function article(array $v=[]):Article{return Article::create($v+['fingerprint'=>bin2hex(random_bytes(20)),'source_title'=>'Goed nieuws','source_name'=>'Bron','source_url'=>'https://example.com','status'=>'published','category'=>'people','published_at'=>now()]);}
 public function test_moment_heart_stays_in_place_and_daily_award_cannot_be_farmed():void {
  $this->travelTo(\Carbon\Carbon::parse('2026-10-17 23:59:00','Europe/Amsterdam'));
  $u=$this->member();$m=$this->moment($this->member());$url='/momentjes/'.$m->id.'/hart';
  $this->actingAs($u)->postJson($url,['liked'=>true])->assertOk()->assertHeaderMissing('Location')->assertJsonPath('hearts_count',1)->assertJsonPath('points_awarded',1);
  $this->postJson($url,['liked'=>false])->assertOk()->assertJsonPath('hearts_count',0)->assertJsonPath('points_awarded',0);
  $this->postJson($url,['liked'=>true])->assertOk()->assertJsonPath('hearts_count',1)->assertJsonPath('points_awarded',0);
  $this->travel(2)->minutes();
  $this->postJson($url,['liked'=>true])->assertOk()->assertJsonPath('hearts_count',1)->assertJsonPath('points_awarded',1);
  $this->assertSame(2,app(Lightpoints::class)->summary($u)['points']);$this->assertDatabaseCount('moment_hearts',1);
 }
 public function test_article_heart_daily_reward_and_own_story_exclusion():void {
  $u=$this->member();$a=$this->article();$url='/news/'.$a->id.'/like';
  $this->actingAs($u)->postJson($url,['liked'=>true])->assertOk()->assertJsonPath('points_awarded',1);
  $this->postJson($url,['liked'=>false])->assertOk();$this->postJson($url,['liked'=>true])->assertOk()->assertJsonPath('points_awarded',0);
  $this->travel(1)->days();$this->postJson($url,['liked'=>true])->assertOk()->assertJsonPath('points_awarded',1)->assertJsonPath('likes_count',1);
  $own=$this->article(['origin'=>'community','author_id'=>$u->id]);$this->postJson('/news/'.$own->id.'/like',['liked'=>true])->assertOk()->assertJsonPath('points_awarded',0);
  $this->assertSame(2,app(Lightpoints::class)->summary($u)['points']);
 }
 public function test_own_private_pending_and_suspended_cannot_reward_hearts():void {
  $u=$this->member();$this->actingAs($u);$own=$this->moment($u);$this->postJson('/momentjes/'.$own->id.'/hart',['liked'=>true])->assertUnprocessable();
  foreach([['visibility'=>'private'],['status'=>'pending']] as $v){$m=$this->moment($this->member(),$v);$this->postJson('/momentjes/'.$m->id.'/hart',['liked'=>true])->assertNotFound();}
  $m=$this->moment($this->member());$u->forceFill(['is_suspended'=>true])->save();$this->postJson('/momentjes/'.$m->id.'/hart',['liked'=>true])->assertForbidden();
  $this->assertDatabaseCount('moment_hearts',0);$this->assertDatabaseCount('lightpoint_events',0);
 }
 public function test_private_and_public_moments_together_earn_only_one_daily_point():void {
  $u=$this->member();$private=$this->moment($u,['visibility'=>'private']);$public=$this->moment($u);$pending=$this->moment($u,['status'=>'pending']);
  $points=app(MomentModerator::class);$points->syncPoints($private);$points->syncPoints($public);$points->syncPoints($pending);$points->syncPoints($private);
  $this->assertSame(1,app(Lightpoints::class)->summary($u)['points']);
  $later=$this->moment($u,['moment_date'=>now('Europe/Amsterdam')->addDay()->toDateString()]);$points->syncPoints($later);
  $this->assertSame(2,app(Lightpoints::class)->summary($u)['points']);
 }
}
