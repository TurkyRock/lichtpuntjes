<?php
namespace Tests\Feature;
use App\Models\Article;
use App\Services\{ArticleImageGenerator,ArticlePhotoFinder,LichtpuntIllustrationPrompt,NewsServiceException};
use Illuminate\Support\Facades\{DB,File,Http};
use Tests\TestCase;
class LichtpuntIllustrationTest extends TestCase {
 private array $files=[];
 protected function setUp():void {parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','news_images.provider'=>'comfyui','news_images.comfy_url'=>'http://127.0.0.1:8188','news_images.checkpoint'=>'sd_xl_base_1.0.safetensors']);DB::purge('sqlite');$this->artisan('migrate',['--force'=>true])->assertExitCode(0);Http::preventStrayRequests();}
 protected function tearDown():void {foreach($this->files as $file)File::delete($file);parent::tearDown();}
 private function article(string $tag='one'):Article {return Article::create(['fingerprint'=>hash('sha256',$tag),'source_title'=>'Ajax celebrates a win','source_name'=>'News','source_url'=>'https://example.com/'.$tag,'category'=>'sport','status'=>'published','nl'=>['title'=>'Ajax viert een overwinning','teaser'=>'Samen blij','body'=>['Het team viert feest.']],'en'=>['title'=>'Ajax celebrates a win','teaser'=>'Team spirit','body'=>['The team celebrates together.']],'photo'=>['generated'=>false,'url'=>'/old.jpg','creator'=>'Old author']]);}
 private function fakeServer(string $bytes):void {
  Http::fake(['http://127.0.0.1:8188/object_info/*'=>Http::response(['CheckpointLoaderSimple'=>['input'=>['required'=>['ckpt_name'=>[['sd_xl_base_1.0.safetensors']]]]]]),'http://127.0.0.1:8188/prompt'=>Http::response(['prompt_id'=>'job-123']),'http://127.0.0.1:8188/history/job-123'=>Http::response(['job-123'=>['status'=>['completed'=>true,'status_str'=>'success'],'outputs'=>['9'=>['images'=>[['filename'=>'result.png','subfolder'=>'','type'=>'output']]]]]]),'http://127.0.0.1:8188/view*'=>Http::response($bytes,200,['Content-Type'=>'image/png'])]);
 }
 public function test_graph_contains_article_specific_scene_brand_style_and_only_local_nodes():void {
  $a=$this->article();$bytes=File::get(public_path('images/lichtpunt-zon.png'));$this->fakeServer($bytes);$this->files[]=public_path('news-images/'.hash('sha256',$bytes).'.png');
  $generator=app(ArticleImageGenerator::class);$generator->check();$p=$generator->generate($a);$this->assertTrue($p['generated']);$this->assertSame(LichtpuntIllustrationPrompt::PHOTO_STYLE,$p['style']);$this->assertSame(hash('sha256',$bytes),$p['sha256']);$this->assertFileExists(public_path(ltrim($p['url'],'/')));
  Http::assertSent(function($r)use($a){if(!str_ends_with($r->url(),'/prompt'))return false;$g=$r['prompt'];$this->assertStringContainsString('Ajax',$g['6']['inputs']['text']);$this->assertStringContainsString('Photorealistic',$g['6']['inputs']['text']);$this->assertStringNotContainsString('linocut',$g['6']['inputs']['text']);$this->assertSame(1024,$g['5']['inputs']['width']);$this->assertSame(576,$g['5']['inputs']['height']);$this->assertSame('lichtpunt-'.$a->id.'-'.$g['3']['inputs']['seed'],$g['9']['inputs']['filename_prefix']);$this->assertArrayNotHasKey('extra_data',$r->data());return true;});
  Http::assertSentCount(4);
 }
 public function test_resume_skips_current_images_and_changed_story_requires_new_one():void {
  $a=$this->article();$bytes=File::get(public_path('images/lichtpunt-zon.png'));$hash=hash('sha256',$bytes);File::ensureDirectoryExists(public_path('news-images'));$path=public_path('news-images/'.$hash.'.png');File::put($path,$bytes);$this->files[]=$path;
  $a->photo=['style'=>LichtpuntIllustrationPrompt::STYLE];$a->update(['photo'=>['url'=>'/news-images/'.$hash.'.png','sha256'=>$hash,'generated'=>true,'style'=>LichtpuntIllustrationPrompt::STYLE,'model'=>config('news_images.checkpoint'),'subject_hash'=>hash('sha256',app(LichtpuntIllustrationPrompt::class)->positive($a))]]);
  $finder=app(ArticlePhotoFinder::class);$this->assertTrue($finder->current($a));$this->assertTrue($finder->attach($a));Http::assertNothingSent();$a->update(['en'=>['title'=>'Ajax helps children','teaser'=>'Community','body'=>['A new project.']]]);$this->assertFalse($finder->current($a));
 }
 public function test_render_failure_keeps_previous_photo_and_published_status():void {
  $a=$this->article();$before=$a->photo;
  Http::fake(['http://127.0.0.1:8188/prompt'=>Http::response(['prompt_id'=>'failed']),'http://127.0.0.1:8188/history/failed'=>Http::response(['failed'=>['status'=>['status_str'=>'error','messages'=>['sensitive internals']]]])]);
  try{app(ArticlePhotoFinder::class)->attach($a);$this->fail('Expected render failure');}catch(NewsServiceException $e){$this->assertStringNotContainsString('sensitive internals',$e->getMessage());}$this->assertSame($before,$a->fresh()->photo);$this->assertSame('published',$a->fresh()->status);
 }
 public function test_duplicate_image_is_rejected_and_is_never_reused():void {
  $one=$this->article();$bytes=File::get(public_path('images/lichtpunt-zon.png'));$one->update(['photo'=>['sha256'=>hash('sha256',$bytes),'generated'=>true]]);$two=$this->article('two');$before=$two->photo;$this->fakeServer($bytes);
  try{app(ArticlePhotoFinder::class)->attach($two);$this->fail('Expected duplicate rejection');}catch(NewsServiceException $e){$this->assertStringContainsString('al door een ander artikel',$e->getMessage());}$this->assertSame($before,$two->fresh()->photo);
 }
 public function test_arbitrary_external_generator_endpoint_is_blocked():void {
  config(['news_images.comfy_url'=>'https://paid-generator.example']);$this->expectException(NewsServiceException::class);app(ArticleImageGenerator::class)->check();
 }
 public function test_check_is_read_only_and_reports_missing_checkpoint():void {
  Http::fake(['http://127.0.0.1:8188/object_info/*'=>Http::response(['CheckpointLoaderSimple'=>['input'=>['required'=>['ckpt_name'=>[[]]]]]])]);$this->artisan('news:illustrations',['--check'=>true])->expectsOutputToContain('COMFYUI_CHECKPOINT ontbreekt')->assertExitCode(1);Http::assertSentCount(1);
 }
 public function test_batch_resumes_completed_articles_without_changing_publication():void {
  $a=$this->article();$bytes=File::get(public_path('images/lichtpunt-zon.png'));$hash=hash('sha256',$bytes);$path=public_path('news-images/'.$hash.'.png');$this->files[]=$path;$this->fakeServer($bytes);
  $before=File::glob(storage_path('app/illustration-backups/*.json'));$reports=File::glob(storage_path('app/illustrations-*.csv'));
  try{$this->artisan('news:illustrations',['--all'=>true])->expectsOutputToContain('1 gemaakt')->assertExitCode(0);$this->assertSame('published',$a->fresh()->status);$this->assertTrue($a->fresh()->photo['generated']);$this->artisan('news:illustrations',['--all'=>true])->expectsOutputToContain('0 gemaakt, 1 al klaar')->assertExitCode(0);Http::assertSentCount(5);$this->assertNotEmpty(array_diff(File::glob(storage_path('app/illustration-backups/*.json')),$before));}
  finally{foreach(array_diff(File::glob(storage_path('app/illustration-backups/*.json')),$before) as $f)$this->files[]=$f;foreach(array_diff(File::glob(storage_path('app/illustrations-*.csv')),$reports) as $f)$this->files[]=$f;}
 }
 public function test_photorealistic_flag_converts_paper_once_and_resumes_without_replacing_articles():void {
  $a=$this->article();$a->photo=['style'=>LichtpuntIllustrationPrompt::STYLE];
  $old=File::get(public_path('images/lichtpunt-zon.png')).'old-paper';$oldHash=hash('sha256',$old);$oldFile=public_path('news-images/'.$oldHash.'.png');File::ensureDirectoryExists(dirname($oldFile));File::put($oldFile,$old);$this->files[]=$oldFile;
  $a->update(['photo'=>['url'=>'/news-images/'.$oldHash.'.png','sha256'=>$oldHash,'generated'=>true,'style'=>LichtpuntIllustrationPrompt::STYLE,'model'=>config('news_images.checkpoint'),'subject_hash'=>hash('sha256',app(LichtpuntIllustrationPrompt::class)->positive($a))]]);
  $bytes=File::get(public_path('images/lichtpunt-zon.png')).'new-photo';$this->fakeServer($bytes);$this->files[]=public_path('news-images/'.hash('sha256',$bytes).'.png');
  $before=File::glob(storage_path('app/illustration-backups/*.json'));$reports=File::glob(storage_path('app/illustrations-*.csv'));
  try {
   $this->artisan('news:illustrations',['--photorealistic'=>true,'--limit'=>1])->expectsOutputToContain('1 gemaakt')->assertExitCode(0);
   $this->assertSame(LichtpuntIllustrationPrompt::PHOTO_STYLE,$a->fresh()->photo['style']);$this->assertSame('published',$a->fresh()->status);$this->assertDatabaseCount('articles',1);$this->assertFileExists($oldFile);
   $this->artisan('news:illustrations',['--photorealistic'=>true,'--limit'=>1])->expectsOutputToContain('0 gemaakt, 1 al klaar')->assertExitCode(0);Http::assertSentCount(5);
  }finally{foreach(array_diff(File::glob(storage_path('app/illustration-backups/*.json')),$before) as $f)$this->files[]=$f;foreach(array_diff(File::glob(storage_path('app/illustrations-*.csv')),$reports) as $f)$this->files[]=$f;}
 }

}
