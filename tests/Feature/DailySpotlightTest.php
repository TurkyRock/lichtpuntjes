<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Article,User};
use App\Services\{DailySpotlight,ArticlePhotoFinder,DailyStoryScore,GeminiJson,NewsImporter};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http};
use Illuminate\Support\Sleep;
class DailySpotlightTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();config(['cache.default'=>'array']);$this->travelTo(\Carbon\Carbon::parse('2026-10-14 10:00:00','Europe/Amsterdam'));$this->mock(ArticlePhotoFinder::class,fn($m)=>$m->shouldReceive('current')->andReturn(true));Http::preventStrayRequests();Sleep::fake();}
 protected function tearDown():void {$this->travelBack();parent::tearDown();}
 private function user(bool $admin=true):User{return User::forceCreate(['name'=>'Sunny','email'=>bin2hex(random_bytes(6)).'@example.com','password'=>'TestPassword123','email_verified_at'=>now(),'community_accepted_at'=>now(),'is_admin'=>$admin]);}
 private function story(array $extra=[]):Article {return Article::create($extra+['fingerprint'=>bin2hex(random_bytes(16)),'origin'=>'news','status'=>'published','source_title'=>'Happy news','source_name'=>'Source','source_url'=>'https://example.com/news','category'=>'nature','published_at'=>now()->subHours(2),'daily_score'=>85,'daily_reason'=>['nl'=>'Breed en hoopvol','en'=>'Broad and hopeful'],'daily_scored_at'=>now(),'photo'=>['url'=>'/news-images/'.str_repeat('a',64).'.png','generated'=>true],'nl'=>['title'=>'Een zonnig verhaal','teaser'=>'Een mooie ontwikkeling','body'=>['Een betrouwbaar positief verhaal.']],'en'=>['title'=>'A sunny story','teaser'=>'A lovely development','body'=>['A reliable uplifting story.']]]);}
 private function edition(Article $a,string $day='2026-10-13'):void {DB::table('daily_spotlights')->insert(['edition_date'=>$day,'article_id'=>$a->id,'category'=>$a->origin==='community'?'community':$a->category,'is_daily'=>true,'manual'=>false,'created_at'=>now(),'updated_at'=>now()]);}
 public function test_choice_is_stable_and_prefers_recent_broad_quality_without_ai_calls():void {
  $fresh=$this->story(['daily_score'=>85]);$old=$this->story(['daily_score'=>99,'published_at'=>now()->subDays(3)]);$this->story(['daily_score'=>100,'status'=>'pending']);
  $service=app(DailySpotlight::class);$this->assertSame($fresh->id,$service->choose()->article_id);
  $this->story(['daily_score'=>100]);$this->assertSame($fresh->id,$service->choose()->article_id);$this->assertSame(1,DB::table('daily_spotlights')->count());Http::assertNothingSent();
 }
 public function test_rotation_excludes_recent_repeats_and_yesterdays_category():void {
  $yesterday=$this->story(['daily_score'=>100]);$this->edition($yesterday);$this->story(['daily_score'=>99,'category'=>'nature']);$other=$this->story(['daily_score'=>80,'category'=>'people']);
  $this->assertSame($other->id,app(DailySpotlight::class)->choose()->article_id);
 }
 public function test_fallback_is_labelled_featured_and_hidden_or_incomplete_stories_never_qualify():void {
  $old=$this->story(['published_at'=>now()->subDays(10)]);$this->edition($old);
  $this->story(['daily_score'=>100,'category'=>'people','hidden_at'=>now()]);$this->story(['daily_score'=>100,'category'=>'people','en'=>['title'=>'Incomplete','body'=>[]]]);
  $chosen=app(DailySpotlight::class)->choose();$this->assertSame($old->id,$chosen->article_id);$this->assertFalse((bool)$chosen->is_daily);
  $old->update(['hidden_at'=>now()]);$this->assertNull(app(DailySpotlight::class)->choose());
 }
 public function test_community_stories_qualify_only_with_active_author():void {
  $author=$this->user(false);$a=$this->story(['origin'=>'community','category'=>'community','author_id'=>$author->id]);
  $this->assertSame($a->id,app(DailySpotlight::class)->choose()->article_id);
  $author->forceFill(['is_suspended'=>true])->save();$this->assertNull(app(DailySpotlight::class)->choose());
 }
 public function test_edition_changes_at_seven_am_amsterdam_including_winter():void {
  $service=app(DailySpotlight::class);
  $this->travelTo(\Carbon\Carbon::parse('2026-10-15 06:59:00','Europe/Amsterdam'));$this->assertSame('2026-10-14',$service->edition());
  $this->travelTo(\Carbon\Carbon::parse('2026-10-15 07:00:00','Europe/Amsterdam'));$this->assertSame('2026-10-15',$service->edition());
  $this->travelTo(\Carbon\Carbon::parse('2026-12-15 07:00:00','Europe/Amsterdam'));$this->assertSame('2026-12-15',$service->edition());
 }
 public function test_weekly_rankings_ignore_old_activity_and_feature_three_different_stories():void {
  $daily=$this->story(['daily_score'=>100]);$popular=$this->story(['daily_score'=>80,'category'=>'people']);$liked=$this->story(['daily_score'=>80,'category'=>'energy']);$old=$this->story(['daily_score'=>80,'category'=>'sport','views_count'=>999,'likes_count'=>999]);$u=$this->user(false);
  foreach([$daily,$popular] as $a)DB::table('article_reads')->insert(['article_id'=>$a->id,'visitor_hash'=>str_repeat((string)$a->id,64),'read_date'=>'2026-10-14']);
  DB::table('article_reads')->insert(['article_id'=>$old->id,'visitor_hash'=>str_repeat('f',64),'read_date'=>'2026-10-01']);
  DB::table('user_article_likes')->insert(['article_id'=>$liked->id,'user_id'=>$u->id,'created_at'=>now()]);
  $cards=app(DailySpotlight::class)->highlights();$this->assertSame(['daily','reads','likes'],array_column($cards,'kind'));$this->assertSame([$daily->id,$popular->id,$liked->id],array_map(fn($c)=>$c['article']->id,$cards));
 }
 public function test_likes_fallback_uses_actual_total_instead_of_newest_story():void {
  $daily=$this->story(['daily_score'=>100]);$read=$this->story(['daily_score'=>80]);
  $liked=$this->story(['daily_score'=>80,'likes_count'=>20,'published_at'=>now()->subDays(2)]);
  $this->story(['daily_score'=>80,'likes_count'=>0,'published_at'=>now()->subHour()]);
  DB::table('article_reads')->insert(['article_id'=>$read->id,'visitor_hash'=>str_repeat('f',64),'read_date'=>'2026-10-14']);
  $cards=app(DailySpotlight::class)->highlights();
  $this->assertSame(['daily','reads','likes_total'],array_column($cards,'kind'));
  $this->assertSame($liked->id,$cards[2]['article']->id);
  $this->get('/')->assertOk()->assertSee('Meeste likes')->assertSee('20 likes in totaal');
 }
 public function test_manual_override_is_admin_only_and_cannot_feature_hidden_story():void {
  $first=$this->story();$next=$this->story(['category'=>'people','daily_score'=>90]);
  $this->actingAs($this->user(false))->post('/beheer/dagverhaal/'.$first->id)->assertForbidden();
  $this->actingAs($this->user())->post('/beheer/dagverhaal/'.$first->id)->assertRedirect();$this->assertTrue((bool)app(DailySpotlight::class)->choose()->manual);
  $first->update(['hidden_at'=>now()]);$this->post('/beheer/dagverhaal/'.$first->id)->assertUnprocessable();
  $this->post('/beheer/dagverhaal/anders')->assertRedirect();
 }
 public function test_score_backfill_uses_bounded_ai_calls_and_homepage_uses_existing_images():void {
  $a=$this->story(['daily_score'=>null,'daily_scored_at'=>null]);
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->once()->andReturn(['daily_score'=>92,'daily_reason_nl'=>'Hoop voor iedereen','daily_reason_en'=>'Hope for everyone']));
  $this->artisan('news:spotlight-score',['--limit'=>1])->assertExitCode(0);$this->assertSame(92,$a->fresh()->daily_score);
  $this->get('/')->assertOk()->assertSee('Lichtpuntje van de dag')->assertSee('data-feature-story="'.$a->id.'"',false)->assertSee('Jouw Lichtpuntje')->assertDontSee('id="earnTitle"',false);
  $this->get('/en')->assertOk()->assertSee('Bright spot of the day')->assertSee('A sunny story');
  $this->get('/nl/onderwerp/nature')->assertOk()->assertDontSee('id="dailyHighlights"',false);
 }
 public function test_scoring_invalid_result_does_not_modify_publication_or_translations():void {
  $a=$this->story(['daily_score'=>null,'daily_scored_at'=>null]);$before=$a->nl;
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->once()->andReturn(['daily_score'=>120]));
  $this->artisan('news:spotlight-score',['--limit'=>1])->assertExitCode(1);$a->refresh();$this->assertSame('published',$a->status);$this->assertSame($before,$a->nl);$this->assertNull($a->daily_score);
 }
 public function test_new_news_gets_its_score_in_the_same_ai_request_without_extra_calls():void {
  config(['news.gemini_key'=>'test-key','news.gemini_model'=>'test-model']);
  $a=$this->story(['status'=>'pending','daily_score'=>null,'daily_scored_at'=>null,'source_description'=>str_repeat('A reliable positive development. ',10)]);
  $text=['title'=>'Goed nieuws','teaser'=>'Een mooie ontwikkeling','body'=>['Een betrouwbare samenvatting.']];
  Http::fake(['generativelanguage.googleapis.com/*'=>Http::response(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode(['decision'=>'suitable','reason'=>'Positief','category'=>'nature','nl'=>$text,'en'=>$text,'daily_score'=>93,'daily_reason_nl'=>'Breed en hoopvol','daily_reason_en'=>'Broad and hopeful'])]]]]]])]);
  app(NewsImporter::class)->assess($a);$a->refresh();$this->assertSame('published',$a->status);$this->assertSame(93,$a->daily_score);Http::assertSentCount(1);
 }
 public function test_invalid_optional_score_does_not_turn_approved_news_into_a_moderation_failure():void {
  $fields=DailyStoryScore::optionalFields(['daily_score'=>120]);$this->assertNull($fields['daily_score']);$this->assertNull($fields['daily_scored_at']);
 }

 public function test_drawn_illustrations_have_no_repeated_caption_but_other_generated_images_keep_theirs():void {
  $a=$this->story(['photo'=>['url'=>'/news-images/'.str_repeat('a',64).'.png','generated'=>true,'style'=>'lichtpunt-paper-v1']]);
  $html=$this->get('/')->assertOk()->getContent();$body=explode('<script',substr($html,strpos($html,'<body')),2)[0];
  $this->assertStringNotContainsString('AI-gegenereerde illustratie',$body);$this->assertStringContainsString('Onze beelden zijn met AI gemaakt',$body);
  $html=$this->get(\App\Support\StorySeo::path($a))->assertOk()->getContent();$body=explode('<script',substr($html,strpos($html,'<body')),2)[0];$this->assertStringNotContainsString('AI-gegenereerde illustratie',$body);$this->assertStringContainsString('Met AI gemaakte samenvatting',$body);
  $a->update(['photo'=>['url'=>'/news-images/'.str_repeat('a',64).'.png','generated'=>true,'style'=>'older-realistic-style']]);
  $html=$this->get(\App\Support\StorySeo::path($a))->assertOk()->getContent();$body=explode('<script',substr($html,strpos($html,'<body')),2)[0];$this->assertStringContainsString('AI-gegenereerde illustratie',$body);
 }

}
