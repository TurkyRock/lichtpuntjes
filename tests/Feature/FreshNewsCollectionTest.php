<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Article,User};
use App\Services\{ArticleImageGenerator,ArticleImageRejected,LichtpuntIllustrationPrompt,NewsCategoryBalance,NewsImporter,NewsServiceException};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache,DB,File,Http};
class FreshNewsCollectionTest extends TestCase {
 use RefreshDatabase;
 private string $state;private array $files=[];private array $backups=[];
 protected function setUp():void {parent::setUp();$this->state=sys_get_temp_dir().'/fresh-test-'.bin2hex(random_bytes(8)).'.json';config(['news_images.fresh_state_file'=>$this->state,'news_images.assessment_delay_ms'=>0,'news_images.provider'=>'comfyui','news.newsdata_key'=>'test-key','news.gemini_key'=>'test-key','cache.default'=>'array']);Http::preventStrayRequests();$this->backups=File::glob(storage_path('app/news-replacement-backups/*.json'));}
 protected function tearDown():void {File::delete(array_merge($this->files,[$this->state,$this->state.'.tmp'],array_diff(File::glob(storage_path('app/news-replacement-backups/*.json')),$this->backups)));parent::tearDown();}
 private function article(array $extra=[]):Article {$tag=bin2hex(random_bytes(8));return Article::create($extra+['fingerprint'=>$tag,'origin'=>'news','status'=>'published','source_title'=>'Positive story '.$tag,'source_name'=>'Test','source_url'=>'https://example.test/'.$tag,'category'=>'nature','published_at'=>now(),'daily_score'=>90,'nl'=>['title'=>'Verhaal '.$tag,'teaser'=>'Goed nieuws.','body'=>['Een positief verhaal.']],'en'=>['title'=>'Story '.$tag,'teaser'=>'Good news.','body'=>['A positive story.']]]);}
 private function image(Article $a):array {$bytes=File::get(public_path('images/lichtpunt-zon.png')).'fresh-'.$a->id;$hash=hash('sha256',$bytes);$path=public_path('news-images/'.$hash.'.png');File::ensureDirectoryExists(dirname($path));File::put($path,$bytes);$this->files[]=$path;return ['url'=>'/news-images/'.$hash.'.png','sha256'=>$hash,'generated'=>true,'style'=>LichtpuntIllustrationPrompt::PHOTO_STYLE,'model'=>config('news_images.checkpoint'),'subject_hash'=>hash('sha256',app(LichtpuntIllustrationPrompt::class)->positive($a))];}
 private function setupImport(?callable $generate=null,?callable $fetch=null):void {
  $this->mock(NewsImporter::class,function($m)use($fetch){$m->shouldReceive('fetchPage')->andReturnUsing($fetch??function($cursor,$category){$ids=[];for($i=0;$i<6;$i++)$ids[]=$this->article(['status'=>'pending','category'=>$category])->id;return ['created'=>$ids,'next'=>null];});$m->shouldReceive('assess')->andReturnUsing(fn($a,$stage)=>$a->update(['status'=>'replacement']));});
  $this->mock(ArticleImageGenerator::class,function($m)use($generate){$m->shouldReceive('check')->andReturnNull();$m->shouldReceive('acceptedModels')->andReturn([config('news_images.checkpoint')]);$m->shouldReceive('recordCompleted')->andReturnNull();$m->shouldReceive('generate')->andReturnUsing($generate??fn($a)=>$this->image($a));});
 }
 public function test_deletes_entire_news_archive_then_publishes_exactly_twenty_balanced_new_articles():void {
  $old=[];foreach(['published','rejected','pending','draft','replacement','insufficient'] as $status)$old[]=$this->article(['status'=>$status,'hidden_at'=>$status==='published'?now():null])->id;
  $community=$this->article(['origin'=>'community']);$this->setupImport();
  $this->artisan('news:fresh')->expectsOutputToContain('20 nieuwe nieuwsartikelen')->assertExitCode(0);
  $this->assertSame(0,Article::whereIn('id',$old)->count());$this->assertSame(20,Article::where('origin','news')->count());$this->assertSame(20,Article::visible()->where('origin','news')->count());$this->assertNotNull($community->fresh());
  $this->assertSame([4,4,3,3,3,3],array_map(fn($c)=>Article::where('origin','news')->where('category',$c)->count(),NewsCategoryBalance::CATEGORIES));
  $state=json_decode(File::get($this->state),true);$backup=json_decode(File::get($state['backup']),true);$this->assertCount(6,$backup['tables']['articles']);$this->assertTrue($state['finished']);
  $this->get('/')->assertOk()->assertSee('id="dailyHighlights"',false);Http::assertNothingSent();
 }
 public function test_old_articles_are_removed_before_first_fetch_and_foreign_key_rows_are_backed_up():void {
  $old=$this->article();$user=User::forceCreate(['name'=>'Reader','email'=>'reader@example.test','password'=>'TestPassword123']);$comment=DB::table('comments')->insertGetId(['article_id'=>$old->id,'user_id'=>$user->id,'body'=>'Mooi nieuws','status'=>'approved','created_at'=>now(),'updated_at'=>now()]);DB::table('comment_hearts')->insert(['comment_id'=>$comment,'user_id'=>$user->id,'created_at'=>now()]);
  $this->setupImport(null,function($cursor,$category)use($old){$this->assertNull($old->fresh());return ['created'=>[],'next'=>null];});
  $this->artisan('news:fresh',['--pages'=>1])->expectsOutputToContain('0/20 klaar')->assertExitCode(0);
  $state=json_decode(File::get($this->state),true);$backup=json_decode(File::get($state['backup']),true);$this->assertCount(1,$backup['tables']['comments']);$this->assertCount(1,$backup['tables']['comment_hearts']);$this->assertSame(0,DB::table('comments')->count());$this->assertDatabaseHas('users',['id'=>$user->id]);
 }
 public function test_bad_image_is_replaced_with_another_candidate_without_aborting_batch():void {
  $rejected=null;$this->setupImport(function($a)use(&$rejected){if($rejected===null){$rejected=$a->id;throw new ArticleImageRejected('Onderwerp onvoldoende herkenbaar.');}return $this->image($a);});
  $this->artisan('news:fresh')->expectsOutputToContain('Ander artikel zoeken')->expectsOutputToContain('20 nieuwe nieuwsartikelen')->assertExitCode(0);
  $this->assertNull(Article::find($rejected));$this->assertSame(20,Article::where('origin','news')->count());$state=json_decode(File::get($this->state),true);$this->assertArrayHasKey((string)$rejected,$state['image_rejections']);
 }
 public function test_provider_outage_resumes_ready_images_and_never_clears_them_again():void {
  $calls=0;$outage=true;$this->setupImport(function($a)use(&$calls,&$outage){$calls++;if($calls===3&&$outage)throw new NewsServiceException('ComfyUI tijdelijk onbereikbaar.');return $this->image($a);});
  $this->artisan('news:fresh')->expectsOutputToContain('ComfyUI tijdelijk')->assertExitCode(1);$state=json_decode(File::get($this->state),true);$ready=$state['ready_ids'];$this->assertCount(2,$ready);$photos=Article::whereIn('id',$ready)->pluck('photo','id')->all();$outage=false;
  $this->artisan('news:fresh')->assertExitCode(0);$this->assertSame(21,$calls);$this->assertSame($photos,Article::whereIn('id',$ready)->pluck('photo','id')->all());$this->assertSame(20,Article::where('origin','news')->count());
 }
 public function test_finished_command_does_not_delete_again_or_call_any_provider():void {
  $this->setupImport();$this->artisan('news:fresh')->assertExitCode(0);$ids=Article::where('origin','news')->pluck('id')->all();$this->mock(NewsImporter::class)->shouldNotReceive('fetchPage');$this->mock(ArticleImageGenerator::class)->shouldNotReceive('check');
  $this->artisan('news:fresh')->expectsOutputToContain('niets opnieuw verwijderd')->assertExitCode(0);$this->assertSame($ids,Article::where('origin','news')->pluck('id')->all());
 }
 public function test_backup_failure_keeps_old_articles_and_never_fetches():void {
  $old=$this->article();$this->setupImport();File::partialMock()->shouldReceive('put')->withArgs(fn($path,$contents)=>str_contains($path,'news-replacement-backups'))->andReturn(false);
  $this->artisan('news:fresh')->expectsOutputToContain('Herstelkopie opslaan mislukt')->assertExitCode(1);$this->assertNotNull($old->fresh());$this->assertSame(1,Article::count());
 }
 public function test_active_batch_or_missing_configuration_cannot_clear_articles():void {
  $old=$this->article();$lock=Cache::lock('lichtpunt-illustration-batch',600);$lock->get();
  try {$this->artisan('news:fresh')->expectsOutputToContain('Er loopt al')->assertExitCode(1);}finally {$lock->release();}
  $this->setupImport();config(['news.gemini_key'=>null]);$this->artisan('news:fresh')->expectsOutputToContain('GEMINI_API_KEY')->assertExitCode(1);$this->assertNotNull($old->fresh());$this->assertFileDoesNotExist($this->state);
 }
 public function test_partial_run_keeps_state_and_continues_to_twenty_without_duplicate_reset():void {
  $this->article();$this->setupImport();$this->artisan('news:fresh',['--pages'=>1])->expectsOutputToContain('4/20 klaar')->assertExitCode(0);$state=json_decode(File::get($this->state),true);$ready=$state['ready_ids'];$backup=$state['backup'];
  $this->artisan('news:fresh')->assertExitCode(0);$this->assertSame(20,Article::where('origin','news')->count());$this->assertSame(4,Article::whereIn('id',$ready)->count());$this->assertSame($backup,json_decode(File::get($this->state),true)['backup']);
 }
 public function test_assessment_failure_shows_provider_diagnosis_logs_context_and_resumes_candidates():void {
  $this->setupImport();$failed=true;
  $this->mock(NewsImporter::class,function($m)use(&$failed){
   $m->shouldReceive('fetchPage')->andReturnUsing(function($cursor,$category){$ids=[];for($i=0;$i<6;$i++)$ids[]=$this->article(['status'=>'pending','category'=>$category])->id;return ['created'=>$ids,'next'=>null];});
   $m->shouldReceive('assess')->andReturnUsing(function($a)use(&$failed){if($failed)throw new \RuntimeException('Gemini HTTP 429.');$a->update(['status'=>'replacement']);});
  });
  \Illuminate\Support\Facades\Log::spy();
  $this->artisan('news:fresh')->expectsOutputToContain('Gemini HTTP 429. Quota of snelheidslimiet bereikt')->assertExitCode(1);
  $before=json_decode(File::get($this->state),true);$this->assertCount(6,$before['new_ids']);
  \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->with('Schone nieuwsimport onderbroken',\Mockery::on(fn($c)=>$c['stap']==='positief nieuws beoordelen'&&$c['artikel_id']===$before['new_ids'][0]));
  $failed=false;$this->artisan('news:fresh')->assertExitCode(0);$this->assertSame(20,Article::where('origin','news')->count());$this->assertSame($before['backup'],json_decode(File::get($this->state),true)['backup']);
 }
 public function test_unexpected_exception_does_not_leak_request_secrets():void {
  $this->setupImport(null,fn()=>throw new \RuntimeException('Request failed api_key=DO_NOT_LEAK_THIS'));
  \Illuminate\Support\Facades\Log::spy();
  $this->artisan('news:fresh')->expectsOutputToContain('Onverwachte fout: RuntimeException')->doesntExpectOutputToContain('DO_NOT_LEAK_THIS')->assertExitCode(1);
  \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->with('Schone nieuwsimport onderbroken',\Mockery::on(fn($c)=>!str_contains(json_encode($c),'DO_NOT_LEAK_THIS')));
 }

 public function test_explicit_new_batch_replaces_finished_news_but_preserves_community_and_archive_backup():void {
  $this->setupImport();$this->artisan('news:fresh')->assertExitCode(0);$old=Article::where('origin','news')->pluck('id')->all();$community=$this->article(['origin'=>'community']);$before=json_decode(File::get($this->state),true);$archives=File::glob(storage_path('app/news-fresh-runs/*.json'));
  try{$this->artisan('news:fresh',['--new-batch'=>true])->assertExitCode(0);$this->assertSame(0,Article::whereIn('id',$old)->count());$this->assertSame(20,Article::where('origin','news')->count());$this->assertNotNull($community->fresh());$after=json_decode(File::get($this->state),true);$this->assertNotSame($before['backup'],$after['backup']);$this->assertFileExists($before['backup']);$this->assertCount(1,array_diff(File::glob(storage_path('app/news-fresh-runs/*.json')),$archives));}finally{$this->files=array_merge($this->files,array_diff(File::glob(storage_path('app/news-fresh-runs/*.json')),$archives));}
 }
 public function test_resume_retries_old_paper_rejections_and_switches_to_photos_without_another_clear():void {
  $this->setupImport();$this->artisan('news:fresh',['--pages'=>1])->assertExitCode(0);$state=json_decode(File::get($this->state),true);$backup=$state['backup'];$oldReady=$state['ready_ids'];$state['image_style']=LichtpuntIllustrationPrompt::COLLAGE_STYLE;$state['image_rejections']=[(string)$oldReady[0]=>'Wrong paper style'];
  foreach($oldReady as $id){$a=Article::findOrFail($id);$photo=$a->photo;$photo['style']=LichtpuntIllustrationPrompt::COLLAGE_STYLE;$photo['requested_style']=LichtpuntIllustrationPrompt::COLLAGE_STYLE;$a->photo=$photo;$a->save();}
  File::put($this->state,json_encode($state));$backups=File::glob(storage_path('app/illustration-backups/*.json'));
  try{$this->artisan('news:fresh')->expectsOutputToContain('Eerdere afkeuringen op papierstijl worden opnieuw geprobeerd')->assertExitCode(0);$after=json_decode(File::get($this->state),true);$this->assertSame($backup,$after['backup']);$this->assertSame([],$after['image_rejections']);$this->assertSame(20,Article::where('photo->style',LichtpuntIllustrationPrompt::PHOTO_STYLE)->count());}finally{$this->files=array_merge($this->files,array_diff(File::glob(storage_path('app/illustration-backups/*.json')),$backups));}
 }

}
