<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Article,Comment,User};
use App\Services\GeminiJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http,Queue,File,RateLimiter};
class CommentBoardTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();Queue::fake();}
 private function person(string $name='Sanne'):User {return User::forceCreate(['name'=>$name,'email'=>uniqid().'@example.test','password'=>'Password12345','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 private function story():Article {return Article::create(['fingerprint'=>bin2hex(random_bytes(16)),'source_title'=>'Een spelcafé brengt de buurt samen','source_name'=>'Bron','source_url'=>'https://example.test/news','status'=>'published','category'=>'nature','published_at'=>now(),'nl'=>['title'=>'Een spelcafé brengt de buurt samen'],'en'=>['title'=>'A games café brings neighbours together']]);}
 private function comment(Article $a,User $u,array $v=[]):Comment {return Comment::create($v+['article_id'=>$a->id,'user_id'=>$u->id,'body'=>'Een vriendelijk bericht.','status'=>'approved']);}
 public function test_conversations_group_all_answers_show_real_totals_and_keep_archived_links_disabled():void {
  $me=$this->person('Erik');$other=$this->person();$a=$this->story();$root=$this->comment($a,$me,['body'=>'Dat wil ik ook in Arnhem :)']);
  $reply=$this->comment($a,$other,['parent_id'=>$root->id,'thread_root_id'=>$root->id,'body'=>'Dat zou gezellig zijn!']);
  $this->comment($a,$me,['parent_id'=>$reply->id,'thread_root_id'=>$root->id,'body'=>'Misschien in ons buurthuis!']);
  $this->comment($a,$other,['parent_id'=>$root->id,'thread_root_id'=>$root->id,'body'=>'Geheime afgewezen tekst','status'=>'rejected','ai_reason'=>'Geheime moderatorreden']);
  DB::table('comment_hearts')->insert(['comment_id'=>$root->id,'user_id'=>$other->id,'created_at'=>now()]);
  $this->actingAs($me);
  foreach(['nl','en'] as $lang){$r=$this->get('/profiel?section=comments&lang='.$lang)->assertOk()->assertSee('Dat zou gezellig zijn!')->assertSee('Misschien in ons buurthuis!')->assertDontSee('Geheime afgewezen tekst')->assertDontSee('Geheime moderatorreden')->assertViewHas('commentTotals',['hearts'=>1,'replies'=>1]);$this->assertSame(1,substr_count($r->getContent(),'id="conversation-'));$r->assertSee('/?story='.$a->id,false);
   if(getenv('COMMENTS_CAPTURE')){File::ensureDirectoryExists(base_path('../verification/comments-render'));File::put(base_path('../verification/comments-render/comments-'.$lang.'.html'),$r->getContent());}
  }
  $a->update(['hidden_at'=>now()]);$this->get('/profiel?section=comments')->assertOk()->assertSee('Dat wil ik ook in Arnhem :)')->assertDontSee('/?story='.$a->id,false)->assertDontSee('/reply?',false);
  $this->postJson('/comments/'.$reply->id.'/heart',['liked'=>true])->assertNotFound();
  $this->postJson('/comments/'.$root->id.'/reply',['body'=>'Mooi nieuws!'])->assertNotFound();
 }
 public function test_private_root_and_feedback_are_never_disclosed_to_other_participants():void {
  $me=$this->person();$a=$this->story();$root=$this->comment($a,$this->person(),['status'=>'rejected','body'=>'Verborgen oorspronkelijke tekst','ai_reason'=>'Privé reden']);
  $this->comment($a,$me,['parent_id'=>$root->id,'thread_root_id'=>$root->id,'body'=>'Mijn eigen afgewezen antwoord','status'=>'rejected','ai_reason'=>'Alleen voor mij']);
  $this->actingAs($me)->get('/profiel?section=comments')->assertOk()->assertSee('Mijn eigen afgewezen antwoord')->assertSee('Alleen voor mij')->assertDontSee('Verborgen oorspronkelijke tekst')->assertDontSee('Privé reden');
  $this->actingAs($this->person())->get('/profiel?section=comments')->assertOk()->assertDontSee('Mijn eigen afgewezen antwoord')->assertDontSee('Alleen voor mij')->assertSee('Ontdek de lichtpuntjes');
 }
 public function test_comment_hearts_are_idempotent_private_and_require_verified_active_users():void {
  $u=$this->person();$owner=$this->person();$a=$this->story();$c=$this->comment($a,$owner);
  $this->actingAs($u);
  for($i=0;$i<2;$i++)$this->postJson('/comments/'.$c->id.'/heart',['liked'=>true])->assertOk()->assertJsonPath('hearts_count',1);
  $this->getJson('/news/'.$a->id.'/comments')->assertOk()->assertJsonPath('comments.0.hearts_count',1)->assertJsonPath('comments.0.hearted',true);
  $this->postJson('/comments/'.$c->id.'/heart',['liked'=>false])->assertOk()->assertJsonPath('hearts_count',0);
  $this->actingAs($owner)->postJson('/comments/'.$c->id.'/heart',['liked'=>true])->assertUnprocessable();
  $u->forceFill(['email_verified_at'=>null])->save();$this->actingAs($u)->postJson('/comments/'.$c->id.'/heart',['liked'=>true])->assertForbidden();
  $u->forceFill(['email_verified_at'=>now(),'is_suspended'=>true])->save();$this->actingAs($u)->postJson('/comments/'.$c->id.'/heart',['liked'=>true])->assertForbidden();
  $this->assertDatabaseCount('lightpoint_events',0);
 }
 public function test_nested_replies_use_original_root_and_are_still_moderated():void {
  $u=$this->person();$a=$this->story();$root=$this->comment($a,$this->person());$reply=$this->comment($a,$u,['parent_id'=>$root->id,'thread_root_id'=>$root->id]);
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->once()->andReturn(['decision'=>'approve','confidence'=>.99,'reason'=>'Vriendelijk.']));
  $this->actingAs($u)->postJson('/comments/'.$reply->id.'/reply',['body'=>'Dat lijkt mij ook heel fijn!'])->assertCreated()->assertJsonPath('status','approved');
  $this->assertDatabaseHas('comments',['body'=>'Dat lijkt mij ook heel fijn!','parent_id'=>$reply->id,'thread_root_id'=>$root->id]);
 }
 public function test_migration_preserves_and_groups_existing_nested_comments():void {
  $migration=require database_path('migrations/2026_10_22_160000_add_comment_conversations_and_hearts.php');$migration->down();
  $u=$this->person();$a=$this->story();$root=$this->comment($a,$u);$reply=$this->comment($a,$u,['parent_id'=>$root->id]);$nested=$this->comment($a,$u,['parent_id'=>$reply->id]);
  DB::table('comment_reviews')->insert(['comment_id'=>$reply->id,'from_status'=>'pending','to_status'=>'approved','note'=>'Bewaar de bestaande keuring','created_at'=>now()]);
  $migration->up();
  $this->assertDatabaseHas('comment_reviews',['comment_id'=>$reply->id,'note'=>'Bewaar de bestaande keuring']);
  $this->assertNull($root->fresh()->thread_root_id);$this->assertEquals($root->id,$reply->fresh()->thread_root_id);$this->assertEquals($root->id,$nested->fresh()->thread_root_id);$this->assertEquals($reply->id,$nested->fresh()->parent_id);$this->assertSame('Een vriendelijk bericht.',$nested->fresh()->body);
 }
 public function test_empty_board_is_styled_and_comment_rate_limit_does_not_share_other_actions():void {
  $u=$this->person();$a=$this->story();$this->actingAs($u);
  $r=$this->get('/profiel?section=comments')->assertOk()->assertSee('conversation-summary')->assertSee('Ontdek de lichtpuntjes');
  if(getenv('COMMENTS_CAPTURE'))File::put(base_path('../verification/comments-render/comments-empty.html'),$r->getContent());
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldNotReceive('ask'));
  for($i=0;$i<4;$i++)$this->postJson('/news/'.$a->id.'/comments',['body'=>'x'])->assertUnprocessable();
  $this->postJson('/news/'.$a->id.'/comments?lang=nl',['body'=>'Een blij bericht'])->assertStatus(429)->assertJsonStructure(['message','retry_after'])->assertSee('Even een korte pauze')->assertDontSee('Too Many Attempts');
  $this->getJson('/news/'.$a->id.'/comments')->assertOk();
 }
}
