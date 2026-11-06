<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\Article;
use App\Services\{ArticleImageGenerator,ArticlePhotoFinder,ComfyUiImageGenerator,GeminiJson,LichtpuntIllustrationPrompt,NewsServiceException};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{File,Http};
class PhotorealArticleImagesTest extends TestCase {
 use RefreshDatabase;
 private array $files=[];
 protected function setUp():void{parent::setUp();config(['news_images.provider'=>'comfyui']);Http::preventStrayRequests();}
 protected function tearDown():void{File::delete($this->files);parent::tearDown();}
 private function article():Article{return Article::create(['fingerprint'=>bin2hex(random_bytes(16)),'source_title'=>'Cedric Alexander earns a title opportunity','source_url'=>'https://example.test/wrestling','source_name'=>'News','category'=>'sport','status'=>'published','origin'=>'news','nl'=>['title'=>'Cedric Alexander verdient kans op TNA X-Division titel','teaser'=>'Een kans op een kampioenschap.','body'=>['Een worstelaar heeft een titelwedstrijd verdiend.']],'en'=>['title'=>'Cedric Alexander earns a title opportunity','teaser'=>'A chance to win a championship.','body'=>['A wrestler earns a title match.']],'photo'=>['url'=>'/old.png','requested_style'=>LichtpuntIllustrationPrompt::PHOTO_STYLE]]);}
 private function brief():array{return ['scene'=>'An unbranded professional wrestling championship belt resting beside an empty wrestling ring','must_show'=>'A recognisable pro wrestling championship belt and ring ropes','avoid'=>'book, football pitch, portraits, logos','people'=>false,'confidence'=>.96];}
 private function candidate(Article $a):array{$bytes=File::get(public_path('images/lichtpunt-zon.png'));$hash=hash('sha256',$bytes);$path=public_path('news-images/'.$hash.'.png');File::ensureDirectoryExists(dirname($path));File::put($path,$bytes);$this->files[]=$path;return ['url'=>'/news-images/'.$hash.'.png','sha256'=>$hash,'generated'=>true,'style'=>LichtpuntIllustrationPrompt::PHOTO_STYLE,'model'=>config('news_images.checkpoint'),'subject_hash'=>hash('sha256',app(LichtpuntIllustrationPrompt::class)->positive($a))];}
 private function review(bool $match=true,bool $photo=true):array{return ['match'=>$match,'photorealistic'=>$photo,'confidence'=>.95,'reason'=>$match?'Passende gordel zichtbaar.':'Een boek in plaats van een worstelgordel.'];}
 public function test_title_led_photo_prompt_and_photo_review_accept_relevant_natural_materials_without_paper():void {
  $a=$this->article();$ai=$this->mock(GeminiJson::class);
  $ai->shouldReceive('ask')->once()->withArgs(function($system,$data){$this->assertStringContainsString('title is the primary subject cue',$system);$this->assertStringContainsString('Cedric Alexander',$data['nl']['title']);return true;})->andReturn($this->brief());
  $ai->shouldReceive('ask')->once()->withArgs(function($system,$data,$schema,$image){$this->assertStringContainsString('generated photorealistic thematic photo',$system);$this->assertStringContainsString('Do NOT require torn paper',$system);$this->assertArrayNotHasKey('paper',$schema['properties']);$this->assertSame('image/png',$image['mime']);return true;})->andReturn($this->review());
  $this->mock(ComfyUiImageGenerator::class)->shouldReceive('generate')->once()->andReturnUsing(function($prepared){$prompt=app(LichtpuntIllustrationPrompt::class)->positive($prepared);$this->assertStringStartsWith('An unbranded professional wrestling championship belt',$prompt);$this->assertStringContainsString('Photorealistic editorial',$prompt);$this->assertStringNotContainsString('paper fibres',$prompt);$this->assertStringNotContainsString('Cedric Alexander',$prompt);$this->assertLessThanOrEqual(2048,strlen(app(LichtpuntIllustrationPrompt::class)->positive($prepared,450)));return $this->candidate($prepared);});
  $result=app(ArticleImageGenerator::class)->generate($a);$this->assertTrue($result['visual_review']['photorealistic']);$a->photo=$result;$this->assertTrue(app(ArticlePhotoFinder::class)->current($a));Http::assertNothingSent();
 }
 public function test_wrong_subject_gets_one_new_candidate_and_can_be_recovered():void {
  $a=$this->article();$this->mock(GeminiJson::class)->shouldReceive('ask')->times(3)->andReturn($this->brief(),$this->review(false),$this->review());
  $attempts=0;$this->mock(ComfyUiImageGenerator::class)->shouldReceive('generate')->twice()->andReturnUsing(function($prepared)use(&$attempts){if($attempts++)$this->assertStringStartsWith('Close view of A recognisable pro wrestling',app(LichtpuntIllustrationPrompt::class)->positive($prepared));return $this->candidate($prepared);});
  $this->assertTrue(app(ArticleImageGenerator::class)->generate($a)['visual_review']['match']);
 }
 public function test_photo_style_still_rejects_two_wrong_subjects_without_replacing_existing_image():void {
  $a=$this->article();$this->mock(GeminiJson::class)->shouldReceive('ask')->times(3)->andReturn($this->brief(),$this->review(false),$this->review(false));$this->mock(ComfyUiImageGenerator::class)->shouldReceive('generate')->twice()->andReturnUsing(fn($p)=>$this->candidate($p));
  try{app(ArticlePhotoFinder::class)->attach($a,false,true,true);$this->fail('Expected rejection');}catch(NewsServiceException $e){$this->assertStringContainsString('twee kandidaten afgekeurd',$e->getMessage());}$this->assertSame('/old.png',$a->fresh()->photo['url']);
 }
 public function test_obvious_digital_illustrations_are_not_approved_as_photos():void {
  $a=$this->article();$this->mock(GeminiJson::class)->shouldReceive('ask')->times(3)->andReturn($this->brief(),$this->review(true,false),$this->review(true,false));$this->mock(ComfyUiImageGenerator::class)->shouldReceive('generate')->twice()->andReturnUsing(fn($p)=>$this->candidate($p));$this->expectException(NewsServiceException::class);app(ArticleImageGenerator::class)->generate($a);
 }
}
