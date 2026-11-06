<?php
namespace Tests\Feature;

use App\Models\Article;
use App\Services\{ArticleImageGenerator,ArticlePhotoFinder,CloudflareIllustrator,ImageBudgetException,LichtpuntIllustrationPrompt,NewsImporter};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{File,Http};
use Tests\TestCase;

class CollageBatchTest extends TestCase
{
 use RefreshDatabase;
 private string $state;
 private array $files=[];
 protected function setUp():void {
  parent::setUp();$this->state=sys_get_temp_dir().'/collage-batch-'.bin2hex(random_bytes(8)).'.json';
  config(['cache.default'=>'array','news_images.collage_batch_state_file'=>$this->state,'news_images.assessment_delay_ms'=>0,'news_images.provider'=>'cloudflare','news_cycle.image_budget_enabled'=>false]);Http::preventStrayRequests();
 }
 protected function tearDown():void {File::delete(array_merge($this->files,[$this->state,$this->state.'.tmp']));parent::tearDown();}
 private function story(array $extra=[]):Article {return Article::create($extra+['fingerprint'=>bin2hex(random_bytes(16)),'source_title'=>'A greener neighbourhood','source_name'=>'Source','source_url'=>'https://example.com/news','origin'=>'news','status'=>'published','category'=>'nature','published_at'=>now(),'nl'=>['title'=>'Een groenere buurt','teaser'=>'Samen planten','body'=>['Een positief verhaal.']],'en'=>['title'=>'A greener neighbourhood','teaser'=>'Planting together','body'=>['A positive story.']]]);}
 private function photo(Article $a,string $style=LichtpuntIllustrationPrompt::COLLAGE_STYLE):array {
  $a->photo=['requested_style'=>$style];$bytes=File::get(public_path('images/lichtpunt-zon.png')).'trial-'.$a->id.'-'.$this->state;$hash=hash('sha256',$bytes);$file=public_path('news-images/'.$hash.'.png');File::ensureDirectoryExists(dirname($file));File::put($file,$bytes);$this->files[]=$file;
  return ['generated'=>true,'style'=>$style,'model'=>CloudflareIllustrator::MODEL,'sha256'=>$hash,'url'=>'/news-images/'.$hash.'.png','subject_hash'=>hash('sha256',app(LichtpuntIllustrationPrompt::class)->positive($a))];
 }
 private function prepare(Article $old,int $complete=30):array {
  $ids=[];foreach(\App\Services\NewsCategoryBalance::CATEGORIES as $category)for($i=0;$i<5;$i++){
   $a=$this->story(['status'=>'replacement','category'=>$category]);if(count($ids)<$complete)$a->update(['photo'=>$this->photo($a)]);$ids[]=$a->id;
  }
  File::put($this->state,json_encode(['target'=>30,'photorealistic'=>false,'collage'=>true,'old_ids'=>[$old->id],'new_ids'=>$ids,'finished'=>false,'category_balanced'=>true,'exhausted'=>false]));return $ids;
 }
 private function backups():array {return File::glob(storage_path('app/news-replacement-backups/*.json'));}

 public function test_trial_swaps_exactly_five_per_category_without_deleting_old_or_community_articles():void {
  $old=$this->story();$community=$this->story(['origin'=>'community','author_id'=>\App\Models\User::forceCreate(['name'=>'Vriend','email'=>'trial@example.com','password'=>'TestPassword123'])->id]);$ids=$this->prepare($old);$before=$this->backups();
  $this->mock(NewsImporter::class,fn($m)=>$m->shouldNotReceive('fetchPage'));
  try{
   $this->artisan('news:collage-batch')->expectsOutputToContain('30 nieuwe positieve artikelen')->assertExitCode(0);
   $this->assertNotNull($old->fresh()->hidden_at);$this->assertSame(32,Article::count());$this->assertNull($community->fresh()->hidden_at);
   $this->assertSame(30,Article::visible()->where('origin','news')->count());foreach(\App\Services\NewsCategoryBalance::CATEGORIES as $category)$this->assertSame(5,Article::visible()->where('origin','news')->where('category',$category)->count());
   $this->artisan('news:collage-batch')->expectsOutputToContain('Deze proefbatch is afgerond')->assertExitCode(0);Http::assertNothingSent();
   $this->get('/')->assertOk()->assertSee('AI-gegenereerde illustratie');
  }finally{$this->files=array_merge($this->files,array_diff($this->backups(),$before));}
 }

 public function test_partial_batch_stays_private_and_uses_separate_progress_from_the_old_hundred():void {
  $old=$this->story();$legacy=$this->state.'.legacy';File::put($legacy,'{"finished":true,"marker":"old100"}');$this->files[]=$legacy;config(['news_images.replacement_state_file'=>$legacy]);
  $this->mock(NewsImporter::class,function($m){$m->shouldReceive('fetchPage')->once()->andReturnUsing(function($cursor,$category){$a=$this->story(['status'=>'pending','category'=>$category]);return ['created'=>[$a->id],'next'=>null];});$m->shouldReceive('assess')->once()->andReturnUsing(fn($a,$stage)=>$a->update(['status'=>'replacement']));});
  $this->artisan('news:collage-batch',['--pages'=>1])->expectsOutputToContain('1/30')->assertExitCode(0);
  $this->assertNull($old->fresh()->hidden_at);$this->assertSame(1,Article::visible()->count());$this->assertSame('{"finished":true,"marker":"old100"}',File::get($legacy));
 }

 public function test_budget_pause_keeps_old_news_and_resumes_without_regenerating_completed_photos():void {
  $old=$this->story();$ids=$this->prepare($old,28);$before=$this->backups();$calls=0;
  $this->mock(ArticleImageGenerator::class,function($m)use(&$calls){$m->shouldReceive('acceptedModels')->andReturn([CloudflareIllustrator::MODEL]);$m->shouldReceive('recordCompleted')->twice()->andReturnNull();$m->shouldReceive('generate')->times(3)->andReturnUsing(function($a)use(&$calls){if(++$calls===2)throw new ImageBudgetException('AI-puntjes: daglimiet bereikt.');return $this->photo($a);});});
  try{
   $this->artisan('news:collage-batch')->expectsOutputToContain('daglimiet')->assertExitCode(1);$this->assertNull($old->fresh()->hidden_at);$this->assertSame(1,Article::visible()->count());
   $this->artisan('news:collage-batch')->assertExitCode(0);$this->assertSame(3,$calls);$this->assertSame(30,Article::visible()->count());$this->assertNotNull($old->fresh()->hidden_at);
  }finally{$this->files=array_merge($this->files,array_diff($this->backups(),$before));}
 }

 public function test_failed_backup_prevents_swapping_the_collection():void {
  $old=$this->story();$this->prepare($old);File::partialMock()->shouldReceive('put')->withArgs(fn($path,$contents)=>str_contains($path,'news-replacement-backups'))->andReturn(false);
  $this->artisan('news:collage-batch')->expectsOutputToContain('Herstelkopie opslaan mislukt')->assertExitCode(1);$this->assertNull($old->fresh()->hidden_at);$this->assertSame(1,Article::visible()->count());
 }

}
