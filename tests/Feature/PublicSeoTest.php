<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Article;
use App\Models\User;
use App\Support\StorySeo;
class PublicSeoTest extends TestCase {
 use RefreshDatabase;
 private function article(array $fields=[]):Article{return Article::create(array_merge(['fingerprint'=>bin2hex(random_bytes(16)),'origin'=>'news','status'=>'published','source_title'=>'Original','source_name'=>'The original publisher','source_url'=>'https://example.org/original','category'=>'nature','published_at'=>now(),'nl'=>['title'=>'Een mooi verhaal','teaser'=>'Een fijne Nederlandse introductie','body'=>['De inhoud staat rechtstreeks in de HTML.']],'en'=>['title'=>'A lovely story','teaser'=>'A warm English introduction','body'=>['The story is available directly in the HTML.']],'photo'=>['url'=>'/images/test.png','description'=>'Een illustratie','generated'=>true]],$fields));}
 public function test_story_has_server_rendered_text_metadata_source_and_no_analytics():void {
  $a=$this->article();$r=$this->get(StorySeo::path($a))->assertOk()->assertSee('De inhoud staat rechtstreeks in de HTML.')->assertSee('https://example.org/original',false)->assertSee('rel="canonical"',false)->assertSee('hreflang="en"',false)->assertSee('application/ld+json',false)->assertDontSee('lichtpuntAnalyticsConfig');
  $this->assertStringNotContainsString('noindex',(string)$r->headers->get('X-Robots-Tag'));
  $this->assertStringContainsString('class="article-page"',$r->getContent());
 }
 public function test_language_is_determined_by_url_even_with_other_session_language():void {
  $a=$this->article();$this->withSession(['lichtpunt_locale'=>'nl'])->get(StorySeo::path($a,'en'))->assertOk()->assertSee('<html lang="en">',false)->assertSee('The story is available directly in the HTML.')->assertSee('href="'.StorySeo::path($a).'"',false);
  $this->withSession(['lichtpunt_locale'=>'en'])->get('/')->assertSee('<html lang="nl">',false);
  $this->get('/en')->assertOk()->assertSee('Your daily dose of positivity.',false);
 }
 public function test_old_links_and_changed_slugs_redirect_without_losing_story():void {
  $a=$this->article();$this->get('/?story='.$a->id.'&lang=en')->assertRedirect(StorySeo::path($a,'en'))->assertStatus(301);
  $this->get('/nl/lichtpuntjes/'.$a->id.'/old-title')->assertRedirect(StorySeo::path($a))->assertStatus(301);
  $this->get('/?lang=en')->assertRedirect('/en')->assertStatus(301);
 }
 public function test_drafts_missing_stories_and_suspended_community_authors_are_not_public():void {
  $a=$this->article(['status'=>'draft']);$this->get(StorySeo::path($a))->assertNotFound()->assertHeader('X-Robots-Tag','noindex');
  $this->get('/nl/lichtpuntjes/999/missing')->assertNotFound();
  $u=User::forceCreate(['name'=>'Hidden','email'=>'hidden@example.com','password'=>'TestPassword123','is_suspended'=>true]);$a=$this->article(['origin'=>'community','author_id'=>$u->id]);$this->get(StorySeo::path($a))->assertNotFound();
  $xml=$this->get('/sitemap.xml')->assertOk()->streamedContent();$this->assertStringNotContainsString('/nl/lichtpuntjes/',$xml);
 }
 public function test_initial_cards_category_links_and_pagination_are_in_html():void {
  for($i=0;$i<13;$i++)$this->article();$r=$this->get('/')->assertOk()->assertSee('Een mooi verhaal')->assertSee('/nl/onderwerp/nature',false)->assertSee('?page=2',false);
  $this->get('/nl/onderwerp/nature?page=2')->assertOk()->assertSee('Pagina 2')->assertSee('rel="canonical"',false);
  $this->get('/nl/onderwerp/nature?page=999')->assertRedirect('/nl/onderwerp/nature?page=2');
 }
 public function test_json_markup_escapes_untrusted_story_text():void {
  $a=$this->article(['nl'=>['title'=>'</script><script>alert(1)</script>','teaser'=>'A "quote"','body'=>['<img src=x onerror=alert(1)>']]]);
  $r=$this->get(StorySeo::path($a))->assertOk();$this->assertStringNotContainsString('<script>alert(1)</script>',$r->getContent());$r->assertSee('&lt;img src=x onerror=alert(1)&gt;',false);
 }
 public function test_sitemap_uses_configured_domain_not_request_host_and_robots_is_dynamic():void {
  config(['app.url'=>'https://newdomain.example']);$a=$this->article();$xml=$this->get('/sitemap.xml')->assertOk()->streamedContent();$this->assertStringContainsString(StorySeo::absolute(StorySeo::path($a)),$xml);$this->assertStringContainsString('hreflang="en"',$xml);$this->assertNotFalse(simplexml_load_string($xml));
  $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://newdomain.example/sitemap.xml',false);
  $this->get('/inloggen')->assertHeader('X-Robots-Tag','noindex');
 }
}
