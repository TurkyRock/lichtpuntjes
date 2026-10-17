<?php
namespace Tests\Feature;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Services\GeminiJson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;
class CommentReplyTest extends TestCase {
 protected function setUp():void {parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);DB::purge('sqlite');$this->artisan('migrate',['--force'=>true])->assertExitCode(0);Notification::fake();}
 private function user():User {$u=User::create(['name'=>'Vriend','email'=>Str::uuid().'@example.com','password'=>'TestPassword123']);$u->forceFill(['email_verified_at'=>now(),'community_accepted_at'=>now(),'invite_code'=>bin2hex(random_bytes(16))])->save();return $u;}
 private function article():Article {return Article::create(['fingerprint'=>hash('sha256',(string)Str::uuid()),'source_title'=>'Goed nieuws','source_name'=>'Bron','source_url'=>'https://example.com/news','status'=>'published','category'=>'people','published_at'=>now(),'nl'=>['title'=>'Goed nieuws','teaser'=>'Mooi','body'=>['Mooi nieuws']],'en'=>['title'=>'Good news','teaser'=>'Nice','body'=>['Good news']]]);}
 public function test_reply_is_reviewed_with_parent_context_and_reply_to_reply_is_allowed():void {
  $u=$this->user();$a=$this->article();$parent=Comment::create(['user_id'=>$u->id,'article_id'=>$a->id,'body'=>'Wat een mooie prestatie!','status'=>'approved']);
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->twice()->withArgs(fn($system,$data,$schema)=>!empty($data['reply_to']['body']))->andReturn(['decision'=>'approve','confidence'=>.99,'reason'=>'Vriendelijk antwoord.']));
  $this->actingAs($u)->postJson('/news/'.$a->id.'/comments',['body'=>'Dank je wel!','parent_id'=>$parent->id])->assertCreated()->assertJsonPath('status','approved');
  $reply=Comment::where('parent_id',$parent->id)->firstOrFail();
  $this->postJson('/news/'.$a->id.'/comments',['body'=>'Jij ook bedankt!','parent_id'=>$reply->id])->assertCreated();
  $this->getJson('/news/'.$a->id.'/comments')->assertOk()->assertSee('Wat een mooie prestatie!');
  $this->assertDatabaseCount('lightpoint_events',2);
 }
 public function test_hidden_cross_article_and_suspended_parents_cannot_receive_replies():void {
  $u=$this->user();$a=$this->article();$other=$this->article();$p=Comment::create(['user_id'=>$u->id,'article_id'=>$other->id,'body'=>'Verborgen tekst','status'=>'approved']);
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldNotReceive('ask'));
  $this->actingAs($u)->postJson('/news/'.$a->id.'/comments',['body'=>'Wat leuk!','parent_id'=>$p->id])->assertUnprocessable();
  $p->update(['article_id'=>$a->id,'status'=>'rejected']);
  $this->postJson('/news/'.$a->id.'/comments',['body'=>'Wat leuk!','parent_id'=>$p->id])->assertUnprocessable();
  $owner=$this->user();$owner->forceFill(['is_suspended'=>true])->save();$p->update(['status'=>'approved','user_id'=>$owner->id]);
  $this->postJson('/news/'.$a->id.'/comments',['body'=>'Wat leuk!','parent_id'=>$p->id])->assertUnprocessable();
  $this->assertDatabaseCount('comments',1);
 }
 public function test_rejected_parent_text_is_not_leaked_through_an_existing_reply():void {
  $u=$this->user();$a=$this->article();$p=Comment::create(['user_id'=>$u->id,'article_id'=>$a->id,'body'=>'Geheime ingetrokken tekst','status'=>'rejected']);
  Comment::create(['user_id'=>$u->id,'article_id'=>$a->id,'parent_id'=>$p->id,'body'=>'Mooi antwoord','status'=>'approved']);
  $this->getJson('/news/'.$a->id.'/comments')->assertOk()->assertDontSee('Geheime ingetrokken tekst')->assertJsonPath('comments.0.parent',null);
 }
 public function test_negative_reply_remains_private_and_earns_no_points():void {
  $u=$this->user();$a=$this->article();$p=Comment::create(['user_id'=>$u->id,'article_id'=>$a->id,'body'=>'Goed gedaan!','status'=>'approved']);
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->andReturn(['decision'=>'reject','confidence'=>.99,'reason'=>'Aanval op een ander.']));
  $this->actingAs($u)->postJson('/news/'.$a->id.'/comments',['body'=>'Wat ben jij stom!','parent_id'=>$p->id])->assertCreated()->assertJsonPath('status','rejected');
  $this->assertDatabaseCount('lightpoint_events',0);auth()->logout();
  $this->getJson('/news/'.$a->id.'/comments')->assertOk()->assertDontSee('Wat ben jij stom!');
 }
 public function test_profile_shows_three_recent_comments_and_badges_are_collapsed():void {
  $u=$this->user();$a=$this->article();
  for($i=1;$i<=5;$i++)Comment::create(['user_id'=>$u->id,'article_id'=>$a->id,'body'=>'Mijn unieke reactie nummer '.$i,'status'=>'rejected']);
  $this->actingAs($u)->get('/profiel?lang=en&section=comments')->assertOk()->assertSee('Mijn unieke reactie nummer 5')->assertSee('Mijn unieke reactie nummer 3')->assertDontSee('Mijn unieke reactie nummer 2')->assertDontSee('<details class="ladder-collection">',false)->assertSee('page=2',false)->assertSee('section=comments',false)->assertSee('lang=en',false);
  $this->get('/profiel?lang=en&section=comments&page=2')->assertOk()->assertSee('Mijn unieke reactie nummer 1')->assertSee('data-account-section="comments"',false);
  $this->get('/profiel?section=badges')->assertOk()->assertSee('<details class="ladder-collection">',false)->assertDontSee('Mijn unieke reactie nummer 5');
 }
}
