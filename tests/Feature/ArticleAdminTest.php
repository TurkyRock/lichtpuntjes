<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Article,User};
use App\Support\StorySeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
class ArticleAdminTest extends TestCase {
 use RefreshDatabase;
 private function user(bool $admin=true):User{return User::forceCreate(['name'=>'Owner','email'=>bin2hex(random_bytes(6)).'@example.com','password'=>'TestPassword123','email_verified_at'=>now(),'community_accepted_at'=>now(),'is_admin'=>$admin]);}
 private function article(array $data=[]):Article{return Article::create(array_merge(['fingerprint'=>bin2hex(random_bytes(16)),'origin'=>'news','status'=>'published','category'=>'nature','source_title'=>'News story','source_name'=>'Source','source_url'=>'https://example.com/story','published_at'=>now(),'nl'=>['title'=>'Een zonnig verhaal','teaser'=>'Intro','body'=>['Een positief bericht']],'en'=>['title'=>'A sunny story','teaser'=>'Intro','body'=>['An uplifting story']]],$data));}
 public function test_guest_member_and_unverified_admin_cannot_manage_articles():void {
  $a=$this->article();$this->get('/beheer/artikelen')->assertRedirect('/inloggen');$this->actingAs($this->user(false))->get('/beheer/artikelen')->assertForbidden();$this->post('/beheer/artikelen/'.$a->id.'/zichtbaarheid',['hidden'=>true])->assertForbidden();
  $u=$this->user();$u->forceFill(['email_verified_at'=>null])->save();$this->actingAs($u)->get('/beheer/artikelen')->assertRedirect('/email/verify');
 }
 public function test_all_articles_list_has_exactly_30_rows_per_page_and_filters():void {
  $admin=$this->user();for($i=0;$i<31;$i++)$this->article();$this->article(['status'=>'rejected','source_title'=>'Unique candidate','nl'=>['title'=>'Unieke kandidaat']]);
  $r=$this->actingAs($admin)->get('/beheer/artikelen')->assertOk()->assertDontSee('Unieke kandidaat');$this->assertSame(30,substr_count($r->getContent(),'data-article-id='));
  $r=$this->get('/beheer/artikelen?page=2')->assertOk();$this->assertSame(1,substr_count($r->getContent(),'data-article-id='));
  $this->get('/beheer/artikelen?q=Unieke')->assertOk()->assertDontSee('Unieke kandidaat');
 }
 public function test_hiding_removes_story_from_feed_page_and_sitemap_and_restore_is_safe():void {
  $a=$this->article();$this->actingAs($this->user())->post('/beheer/artikelen/'.$a->id.'/zichtbaarheid',['hidden'=>true])->assertRedirect();$this->assertNotNull($a->fresh()->hidden_at);
  $this->getJson('/news/feed')->assertJsonCount(0,'articles');$this->get(StorySeo::path($a))->assertNotFound();$xml=$this->get('/sitemap.xml')->streamedContent();$this->assertStringNotContainsString(StorySeo::path($a),$xml);
  $this->get('/beheer/artikelen?status=hidden')->assertSee('Een zonnig verhaal');$this->post('/beheer/artikelen/'.$a->id.'/zichtbaarheid',['hidden'=>false])->assertRedirect();$this->assertNull($a->fresh()->hidden_at);$this->getJson('/news/feed')->assertJsonCount(1,'articles');
 }
 public function test_controls_do_not_publish_rejected_news_or_restore_suspended_author():void {
  $a=$this->article(['status'=>'rejected']);$this->actingAs($this->user())->post('/beheer/artikelen/'.$a->id.'/zichtbaarheid',['hidden'=>false])->assertUnprocessable();$this->assertSame('rejected',$a->fresh()->status);
  $u=$this->user(false);$u->forceFill(['is_suspended'=>true])->save();$a=$this->article(['origin'=>'community','author_id'=>$u->id,'hidden_at'=>now()]);$this->post('/beheer/artikelen/'.$a->id.'/zichtbaarheid',['hidden'=>false])->assertUnprocessable();$this->assertNotNull($a->fresh()->hidden_at);
 }
 public function test_admin_entry_is_in_profile_not_main_menu_and_titles_are_escaped():void {
  $this->actingAs($this->user());$r=$this->get('/')->assertOk();$header=explode('</header>',$r->getContent())[0];$this->assertStringNotContainsString('href="/beheer?',$header);$this->get('/profiel')->assertSee('href="/beheer/artikelen?lang=nl"',false);
  $this->article(['nl'=>['title'=>'<script>alert(1)</script>']]);$r=$this->get('/beheer/artikelen')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false);$this->assertStringNotContainsString('<script>alert(1)</script>',$r->getContent());
 }
}
