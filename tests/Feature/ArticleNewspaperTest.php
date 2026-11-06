<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Article,User};
use App\Support\StorySeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http,Notification,Queue,File};
use Illuminate\Support\Str;
class ArticleNewspaperTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();Notification::fake();Queue::fake();}
 private function story(array $extra=[]):Article {return Article::create($extra+['fingerprint'=>(string)Str::uuid(),'origin'=>'news','status'=>'published','source_title'=>'Herstel in de natuur','source_name'=>'Een echte nieuwsbron','source_url'=>'https://example.org/goed-nieuws','category'=>'nature','published_at'=>'2026-10-23 07:00:00','views_count'=>2345,'likes_count'=>12,'nl'=>['title'=>'Een nieuwe kans voor de natuur','teaser'=>'Een klein gebaar maakt een groot verschil.','body'=>['Een beschermd natuurgebied krijgt nieuw leven. Met aandacht voor de omgeving ontstaat er weer ruimte voor planten en dieren.','Onderzoekers zien hoopvolle signalen. Het project laat zien wat samenwerking en goede bescherming kunnen betekenen.']],'en'=>['title'=>'A new chance for nature','teaser'=>'Small actions can make a difference.','body'=>['A protected area is coming back to life. Care for its surroundings gives plants and animals room to recover.','Researchers see hopeful signs. The project shows what cooperation and protection can achieve.']],'photo'=>['url'=>'/news-images/'.str_repeat('a',64).'.png','generated'=>true,'style'=>'lichtpunt-collage-v1','description'=>'Een vrolijk natuurbeeld.']]);}
 private function member(array $extra=[]):User {return User::forceCreate($extra+['name'=>'Zonnetje','email'=>Str::uuid().'@example.test','password'=>'An existing sunny password','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 private function capture($r,string $name):void {if(!getenv('NEWSPAPER_CAPTURE'))return;File::ensureDirectoryExists(base_path('../article-verification/render'));File::put(base_path('../article-verification/render/'.$name.'.html'),$r->getContent());}
 public function test_article_is_server_rendered_in_both_languages_with_real_stats_source_and_no_summary_label():void {
  $a=$this->story();foreach(['nl','en'] as $lang){$r=$this->get(StorySeo::path($a,$lang))->assertOk()->assertSee('class="newspaper-title"',false)->assertSee('class="newspaper-lead ',false)->assertSee('data-newspaper-like',false)->assertSee('class="newspaper-ribbon"',false)->assertSee($lang==='nl'?'2.345':'2,345')->assertSee($a->{$lang}['body'][0])->assertSee('https://example.org/goed-nieuws',false)->assertDontSee('class="summary-label"',false)->assertSee('application/ld+json',false)->assertSee('rel="canonical"',false)->assertDontSee("@include('article",false);$this->capture($r,'guest-'.$lang);}
  $this->assertSame(2345,$a->fresh()->views_count);$this->assertSame(12,$a->fresh()->likes_count);Http::assertNothingSent();Queue::assertNothingPushed();Notification::assertNothingSent();
 }
 public function test_homepage_and_popup_use_same_renderer_without_changing_feed_contract():void {
  $a=$this->story();$r=$this->get('/')->assertOk()->assertSee('class="newspaper-reader"',false)->assertSee('/css/article-newspaper.css',false)->assertSee('data-newspaper-story',false)->assertSee('function newspaperLikeContents',false);
  $this->assertSame(1,substr_count($r->getContent(),'function renderArticle()'));$this->assertSame(1,substr_count($r->getContent(),'function communityMarkup('));$this->capture($r,'home');
  $this->getJson('/news/'.$a->id.'/story')->assertOk()->assertJsonPath('views_count',2345)->assertJsonPath('likes_count',12)->assertJsonPath('nl.title',$a->nl['title']);
 }
 public function test_community_article_keeps_own_author_and_report_action_without_external_source_button():void {
  $u=$this->member();$a=$this->story(['origin'=>'community','author_id'=>$u->id,'source_name'=>'Zonnetje','source_url'=>'https://example.org/should-not-be-a-source']);
  $r=$this->actingAs($u)->get(StorySeo::path($a))->assertOk()->assertSee('Van onze community')->assertSee('Zonnetje');
  $doc=new \DOMDocument();@$doc->loadHTML($r->getContent());$xpath=new \DOMXPath($doc);$this->assertSame(0,$xpath->query('//div[contains(@class,"sourcebox")]')->length);$this->capture($r,'community-nl');
  $this->getJson('/news/'.$a->id.'/comments')->assertOk()->assertJsonPath('can_comment',true);
 }
 public function test_missing_photo_long_titles_and_untrusted_text_remain_readable_and_escaped():void {
  $a=$this->story(['photo'=>null,'nl'=>['title'=>str_repeat('Een hoopvol verhaal ',8).'<script>bad()</script>','teaser'=>'','body'=>['<img src=x onerror=bad()>','Gewone tekst blijft gewone tekst.']]]);
  $r=$this->get(StorySeo::path($a))->assertOk()->assertSee('news-without-photo')->assertSee('&lt;img src=x onerror=bad()&gt;',false)->assertDontSee('<script>bad()</script>',false);$this->capture($r,'long-no-photo');
 }
 public function test_authenticated_and_unverified_readers_keep_existing_permissions_and_no_private_comments_are_leaked():void {
  $u=$this->member();$a=$this->story();$this->actingAs($u);$r=$this->get(StorySeo::path($a))->assertOk();$this->capture($r,'member-nl');
  $this->postJson('/news/'.$a->id.'/like',['liked'=>true])->assertOk()->assertJsonPath('liked',true)->assertJsonPath('likes_count',13);
  $this->postJson('/news/'.$a->id.'/like',['liked'=>false])->assertOk()->assertJsonPath('liked',false)->assertJsonPath('likes_count',12);
  $u->forceFill(['email_verified_at'=>null])->save();$this->getJson('/news/'.$a->id.'/comments')->assertOk()->assertJsonPath('can_comment',false);$r=$this->get(StorySeo::path($a))->assertOk();$this->capture($r,'unverified-nl');
 }
 public function test_related_links_use_real_visible_articles_and_preserve_language():void {
  $a=$this->story();$b=$this->story(['nl'=>['title'=>'Een ander lichtpuntje','teaser'=>'','body'=>['Een mooi bericht.']],'en'=>['title'=>'Another bright spot','teaser'=>'','body'=>['A lovely story.']]]);
  $this->story(['status'=>'draft','source_title'=>'DRAFT_NOT_PUBLIC']);$this->story(['hidden_at'=>now(),'source_title'=>'HIDDEN_NOT_PUBLIC']);
  foreach(['nl','en'] as $lang){$r=$this->get(StorySeo::path($a,$lang))->assertOk()->assertViewHas('relatedArticles',fn($items)=>$items->count()===1&&$items->first()->id===$b->id)->assertSee('data-related-story="'.$b->id.'"',false)->assertSee(StorySeo::path($b,$lang),false)->assertDontSee('DRAFT_NOT_PUBLIC')->assertDontSee('HIDDEN_NOT_PUBLIC');$this->capture($r,'related-'.$lang);}
 }
}
