<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,Article};
use App\Services\Lightpoints;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
class LightpointNoticeTest extends TestCase {
 use RefreshDatabase;
 private function user():User{return User::forceCreate(['name'=>'Sunny','email'=>bin2hex(random_bytes(5)).'@example.com','password'=>'TestPassword123','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 public function test_read_reward_is_once_per_day_and_notification_is_delivered_once():void {
  $u=$this->user();$a=Article::create(['fingerprint'=>bin2hex(random_bytes(16)),'source_title'=>'Good news','source_name'=>'Source','source_url'=>'https://example.com','status'=>'published','category'=>'nature','nl'=>['title'=>'Goed','teaser'=>'Mooi','body'=>['Fijn']],'en'=>['title'=>'Good','teaser'=>'Nice','body'=>['Happy']]]);
  $this->actingAs($u)->postJson('/news/'.$a->id.'/read')->assertOk();
  $this->postJson('/news/'.$a->id.'/read')->assertOk();
  $this->assertSame(1,DB::table('lightpoint_events')->where('user_id',$u->id)->sum('points'));
  $this->postJson('/lichtpuntjes/meldingen')->assertOk()->assertJsonPath('notice.points',1)->assertJsonPath('notice.badge.id','switch')->assertJsonPath('notice.label','Je dagelijkse dosis goed nieuws');
  $this->postJson('/lichtpuntjes/meldingen')->assertJsonPath('notice',null);
 }
 public function test_rewards_earned_while_away_are_delivered_after_login_and_other_users_are_private():void {
  $u=$this->user();$other=$this->user();$points=app(Lightpoints::class);
  $points->award($u,'comment:1','comment',1,'Friendly',5);$points->award($other,'invite:1','invite',5,'Invite');
  $this->actingAs($u)->postJson('/lichtpuntjes/meldingen')->assertJsonPath('notice.points',1)->assertJsonPath('notice.total',1);
  $this->postJson('/lichtpuntjes/meldingen')->assertJsonPath('notice',null);
  $this->actingAs($other)->postJson('/lichtpuntjes/meldingen?lang=en')->assertJsonPath('notice.points',5)->assertJsonPath('notice.badge.name','Smile starter');
 }
 public function test_revoked_rewards_are_not_announced_and_access_is_protected():void {
  $this->postJson('/lichtpuntjes/meldingen')->assertUnauthorized();
  $u=$this->user();app(Lightpoints::class)->award($u,'comment:2','comment',1,'Nice');DB::table('lightpoint_events')->where('user_id',$u->id)->update(['active'=>false]);
  $this->actingAs($u)->postJson('/lichtpuntjes/meldingen')->assertJsonPath('notice',null);
  $u->forceFill(['is_suspended'=>true])->save();$this->postJson('/lichtpuntjes/meldingen')->assertForbidden();
 }
 public function test_sidebar_is_admin_only_and_notification_assets_are_member_only():void {
  $this->get('/')->assertDontSee('id="lightpoint-notice"',false);
  $u=$this->user();$this->actingAs($u)->get('/profiel')->assertSee('id="lightpoint-notice"',false)->assertDontSee('class="admin-sidebar"',false);
  $u->forceFill(['is_admin'=>true])->save();$this->get('/beheer')->assertOk()->assertSee('class="admin-sidebar"',false)->assertSee('Alles is rustig');
 }
}
