<?php
namespace Tests\Feature;
use App\Models\Article;
use App\Services\{ArticleImageGenerator,ArticlePhotoFinder,ArticleVisualBrief,ComfyUiImageGenerator,GeminiJson,LichtpuntIllustrationPrompt,NewsServiceException};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{File,Http};
use Illuminate\Support\Sleep;
use Tests\TestCase;
class ColourArticleImagesTest extends TestCase
{
 use RefreshDatabase;
 private array $files=[];
 protected function setUp():void {parent::setUp();config(['news_images.provider'=>'comfyui']);Http::preventStrayRequests();}
 protected function tearDown():void {File::delete($this->files);parent::tearDown();}
 private function article(array $extra=[]):Article {return Article::create($extra+['fingerprint'=>bin2hex(random_bytes(16)),'source_title'=>'GLP-1 medicines help patients','source_url'=>'https://example.test/medicine','source_name'=>'News','category'=>'science','status'=>'published','origin'=>'news','nl'=>['title'=>'GLP-1-medicijnen','teaser'=>'Mogelijk minder complicaties.','body'=>['Een analyse van operatiedossiers.']],'en'=>['title'=>'GLP-1 medicines','teaser'=>'Fewer complications.','body'=>['An analysis of surgical records.']],'photo'=>['requested_style'=>LichtpuntIllustrationPrompt::COLLAGE_STYLE,'url'=>'/old.png']]);}
 private function brief():array {return ['scene'=>'Unbranded medicine injection pens and medicine packaging on a warm beige surface with terracotta and yellow paper accents','must_show'=>'Recognisable medicine pens and packaging','avoid'=>'books, plants, microscope, people','people'=>false,'confidence'=>.95];}
 private function review(bool $match=true):array {return ['match'=>$match,'cheerful'=>true,'paper'=>true,'confidence'=>.95,'reason'=>$match?'Medicijnen duidelijk zichtbaar.':'Er staat een boek in plaats van medicijnen.'];}
 private function candidate(Article $a):array {
  $bytes=File::get(public_path('images/lichtpunt-zon.png'));$hash=hash('sha256',$bytes);$path=public_path('news-images/'.$hash.'.png');File::ensureDirectoryExists(dirname($path));File::put($path,$bytes);$this->files[]=$path;
  return ['url'=>'/news-images/'.$hash.'.png','sha256'=>$hash,'generated'=>true,'style'=>LichtpuntIllustrationPrompt::COLLAGE_STYLE,'model'=>config('news_images.checkpoint'),'subject_hash'=>hash('sha256',app(LichtpuntIllustrationPrompt::class)->positive($a))];
 }
 public function test_bilingual_context_is_read_and_only_literal_brief_reaches_local_model():void {
  $a=$this->article();$ai=$this->mock(GeminiJson::class);
  $ai->shouldReceive('ask')->once()->withArgs(function($system,$data,$schema){$this->assertStringContainsString('actual animal/plant',$system);$this->assertSame('Een analyse van operatiedossiers.',$data['nl']['body'][0]);$this->assertSame('An analysis of surgical records.',$data['en']['body'][0]);return true;})->andReturn($this->brief());
  $ai->shouldReceive('ask')->once()->withArgs(function($system,$data,$schema,$image){$this->assertStringContainsString('IMAGE itself',$system);$this->assertSame('image/png',$image['mime']);$this->assertNotEmpty($image['bytes']);return true;})->andReturn($this->review());
  $this->mock(ComfyUiImageGenerator::class)->shouldReceive('generate')->once()->andReturnUsing(function($prepared){$p=app(LichtpuntIllustrationPrompt::class);$prompt=$p->positive($prepared);$this->assertStringStartsWith('Unbranded medicine injection pens',$prompt);$this->assertStringContainsString('Finely layered hand-torn paper',$prompt);$this->assertStringContainsString('forest green, olive green, sunny yellow, terracotta orange, warm brown and beige',$prompt);$this->assertStringNotContainsString('"article"',$prompt);$this->assertStringNotContainsString('operatiedossiers',$prompt);$this->assertStringContainsString('books, plants',$p->negative($prepared));return $this->candidate($prepared);});
  $photo=app(ArticleImageGenerator::class)->generate($a);$this->assertTrue($photo['visual_review']['match']);$this->assertArrayHasKey('content_hash',$photo['visual_brief']);$this->assertSame('/old.png',$a->fresh()->photo['url']);Http::assertNothingSent();
 }
 public function test_two_wrong_images_never_replace_photo_or_publication():void {
  $a=$this->article();$before=$a->photo;
  $this->mock(GeminiJson::class)->shouldReceive('ask')->times(3)->andReturn($this->brief(),$this->review(false),$this->review(false));
  $this->mock(ComfyUiImageGenerator::class)->shouldReceive('generate')->twice()->andReturnUsing(fn($prepared)=>$this->candidate($prepared));
  try {app(ArticlePhotoFinder::class)->attach($a,false,true,true);$this->fail('Expected rejection');}catch(NewsServiceException $e){$this->assertStringContainsString('twee kandidaten',$e->getMessage());}
  $this->assertSame($before,$a->fresh()->photo);$this->assertSame('published',$a->fresh()->status);
 }
 public function test_second_candidate_can_pass_and_is_skipped_on_resume():void {
  $a=$this->article();$before=File::glob(storage_path('app/illustration-backups/*.json'));
  $this->mock(GeminiJson::class)->shouldReceive('ask')->times(3)->andReturn($this->brief(),$this->review(false),$this->review());
  $this->mock(ComfyUiImageGenerator::class)->shouldReceive('generate')->twice()->andReturnUsing(fn($prepared)=>$this->candidate($prepared));
  try {app(ArticlePhotoFinder::class)->attach($a,false,true,true);$a->refresh();$this->assertTrue(app(ArticlePhotoFinder::class)->current($a));$this->assertTrue(app(ArticlePhotoFinder::class)->attach($a));$a->update(['nl'=>['title'=>'Ander onderwerp','body'=>['Een nieuw verhaal.']],'en'=>null]);$this->assertFalse(app(ArticlePhotoFinder::class)->current($a));}
  finally {$this->files=array_merge($this->files,array_diff(File::glob(storage_path('app/illustration-backups/*.json')),$before));}
 }
 public function test_uncertain_species_stops_before_spending_image_budget():void {
  $brief=$this->brief();$brief['confidence']=.4;$this->mock(GeminiJson::class)->shouldReceive('ask')->once()->andReturn($brief);$this->mock(ComfyUiImageGenerator::class)->shouldNotReceive('generate');$this->expectException(NewsServiceException::class);$this->expectExceptionMessage('te onzeker');app(ArticleImageGenerator::class)->generate($this->article(['source_title'=>'Black abalone recovers']));
 }
 public function test_review_outage_keeps_old_photo_and_does_not_generate_more_candidates():void {
  $a=$this->article();Sleep::fake();$ai=$this->mock(GeminiJson::class);$ai->shouldReceive('ask')->once()->ordered()->andReturn($this->brief());$ai->shouldReceive('ask')->times(3)->ordered()->andThrow(new NewsServiceException('Gemini HTTP 503 (provider tijdelijk niet beschikbaar).'));
  $this->mock(ComfyUiImageGenerator::class)->shouldReceive('generate')->once()->andReturnUsing(fn($prepared)=>$this->candidate($prepared));
  try {app(ArticleImageGenerator::class)->generate($a);$this->fail('Expected outage');}catch(NewsServiceException $e){$this->assertStringContainsString('503',$e->getMessage());}
  $this->assertSame('/old.png',$a->fresh()->photo['url']);Sleep::assertSleptTimes(72);
 }
 public function test_temporary_ai_error_retries_after_sixty_then_three_hundred_seconds():void {
  Sleep::fake();$ai=$this->mock(GeminiJson::class);$ai->shouldReceive('ask')->twice()->ordered()->andThrow(new NewsServiceException('Gemini HTTP 503.'));$ai->shouldReceive('ask')->once()->ordered()->andReturn($this->brief());$this->assertSame(.95,app(ArticleVisualBrief::class)->plan($this->article())['confidence']);Sleep::assertSleptTimes(72);
 }
 public function test_old_collage_styles_remain_explicitly_available():void {
  $a=$this->article(['photo'=>['style'=>'lichtpunt-torn-paper-v2']]);$this->assertSame(LichtpuntIllustrationPrompt::COLLAGE_STYLE,app(LichtpuntIllustrationPrompt::class)->style($a));
 }
 public function test_batch_only_selects_public_news_and_not_hidden_or_personal_stories():void {
  $public=$this->article();$hidden=$this->article(['hidden_at'=>now()]);$community=$this->article(['origin'=>'community']);$draft=$this->article(['status'=>'draft']);$this->mock(ArticleImageGenerator::class)->shouldReceive('check')->once()->andReturnNull();$this->mock(ArticlePhotoFinder::class,function($m)use($public){$m->shouldReceive('current')->once()->andReturn(false);$m->shouldReceive('attach')->once()->withArgs(fn($a)=>$a->id===$public->id)->andReturn(true);});$before=File::glob(storage_path('app/illustrations-*.csv'));
  try {$this->artisan('news:illustrations',['--collage'=>true,'--all'=>true])->expectsOutputToContain('1 gemaakt')->assertExitCode(0);}finally {$this->files=array_merge($this->files,array_diff(File::glob(storage_path('app/illustrations-*.csv')),$before));}
  foreach([$hidden,$community,$draft] as $a)$this->assertSame('/old.png',$a->fresh()->photo['url']);
 }
}
