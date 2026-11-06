<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{File,Http};
use App\Models\{Article,User};
use App\Services\{ArticlePhotoFinder,LichtpuntIllustrationPrompt};
class CollageTrialTest extends TestCase {
 use RefreshDatabase;
 private string $state;
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();$this->state=storage_path('app/article-collage-trial.json');if(is_file($this->state))rename($this->state,$this->state.'.test-original');}
 protected function tearDown():void {File::delete($this->state);if(is_file($this->state.'.test-original'))rename($this->state.'.test-original',$this->state);parent::tearDown();}
 private function article(int $i):Article{return Article::create(['fingerprint'=>hash('sha256','collage-'.$i),'source_title'=>'A hopeful discovery '.$i,'source_name'=>'Test','source_url'=>'https://example.test/'.$i,'status'=>'published','origin'=>'news','category'=>'nature','published_at'=>now()->subMinutes($i),'en'=>['title'=>'A new giant waterlily','teaser'=>'A quiet pond'],'photo'=>['url'=>'/old-'.$i.'.jpg']]);}
 public function test_collage_prompt_changes_image_style_and_preserves_photo_prompt():void {$a=$this->article(1);$p=new LichtpuntIllustrationPrompt;$photo=$p->positive($a);$a->photo=['requested_style'=>LichtpuntIllustrationPrompt::COLLAGE_STYLE];$this->assertStringContainsString('paper fibres',$p->positive($a));$this->assertStringContainsString('no people',strtolower($p->positive($a)));$this->assertStringNotContainsString('tactile torn-paper',$photo);}
 public function test_trial_is_limited_resumable_and_reversible_without_regenerating():void {
  foreach(range(1,5) as $i)$this->article($i);$counter=(object)['calls'=>0];
  $this->mock(ArticlePhotoFinder::class,fn($mock)=>$mock->shouldReceive('attach')->times(3)->andReturnUsing(function($a)use($counter){$counter->calls++;$a->update(['photo'=>['url'=>'/new-'.$a->id.'.png','sha256'=>str_repeat((string)$a->id,64),'style'=>LichtpuntIllustrationPrompt::COLLAGE_STYLE]]);return true;}));
  $this->artisan('news:collage-trial')->assertExitCode(0);$this->assertSame(3,$counter->calls);$this->assertSame(3,Article::where('photo->style',LichtpuntIllustrationPrompt::COLLAGE_STYLE)->count());$this->artisan('news:collage-trial')->assertExitCode(0);$this->assertSame(3,$counter->calls);
  $this->artisan('news:collage-trial',['--restore'=>true])->assertExitCode(0);$this->assertSame(0,Article::where('photo->style',LichtpuntIllustrationPrompt::COLLAGE_STYLE)->count());$this->assertDatabaseCount('articles',5);Http::assertNothingSent();
 }
 public function test_trial_restore_preserves_newer_image_edits():void {
  $a=$this->article(1);File::put($this->state,json_encode(['version'=>1,'articles'=>[['id'=>$a->id,'before'=>['url'=>'/original.jpg'],'state'=>'ready','generated_sha'=>str_repeat('a',64)]]]));$a->update(['photo'=>['url'=>'/newer.jpg','sha256'=>str_repeat('b',64),'style'=>LichtpuntIllustrationPrompt::COLLAGE_STYLE]]);$this->artisan('news:collage-trial',['--restore'=>true])->assertExitCode(0);$this->assertSame('/newer.jpg',$a->fresh()->photo['url']);
 }
 public function test_restore_also_recognises_images_from_previous_collage_version():void {
  $a=$this->article(1);$sha=str_repeat('a',64);File::put($this->state,json_encode(['version'=>1,'articles'=>[['id'=>$a->id,'before'=>['url'=>'/original.jpg'],'state'=>'ready','generated_sha'=>$sha]]]));$a->update(['photo'=>['url'=>'/old-collage.jpg','sha256'=>$sha,'style'=>'lichtpunt-torn-paper-v2']]);$this->artisan('news:collage-trial',['--restore'=>true])->expectsOutputToContain('1 proefbeelden teruggezet')->assertExitCode(0);$this->assertSame('/original.jpg',$a->fresh()->photo['url']);Http::assertNothingSent();
 }
 public function test_completed_preview_route_is_removed_and_no_generation_happens_on_get():void {
  $this->get('/beheer/beeldproef')->assertNotFound();$u=User::forceCreate(['name'=>'Bezoeker','email'=>'test@example.test','password'=>'LongTestPassword123','email_verified_at'=>now(),'community_accepted_at'=>now()]);$this->actingAs($u)->get('/beheer/beeldproef')->assertNotFound();$u->forceFill(['is_admin'=>true])->save();$this->get('/beheer/beeldproef')->assertNotFound();Http::assertNothingSent();
 }
}
