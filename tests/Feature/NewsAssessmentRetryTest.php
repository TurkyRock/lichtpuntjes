<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\Article;
use App\Services\{NewsImporter,NewsAssessmentRetry,GeminiTemporaryException};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\{Sleep,Str};
use Illuminate\Support\Facades\Http;
class NewsAssessmentRetryTest extends TestCase
{
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();Sleep::fake();Http::preventStrayRequests();config(['news.gemini_key'=>'test','news.gemini_model'=>'test-model','news_images.assessment_delay_ms'=>0]);}
 protected function tearDown():void {Sleep::fake(false);parent::tearDown();}
 private function article():Article {return Article::create(['fingerprint'=>hash('sha256',Str::uuid()),'source_title'=>'Vrijwilligers planten bloemen in hun buurt','source_description'=>str_repeat('Vrijwilligers maken hun buurt samen groen met nieuwe bloemen. ',5),'source_name'=>'Bron','source_url'=>'https://example.test/news','status'=>'pending','category'=>'nature']);}
 public function test_503_retries_after_sixty_then_three_hundred_seconds_and_keeps_same_article():void {
  $a=$this->article();$data=['decision'=>'suitable','reason'=>'Positief nieuws','category'=>'nature','nl'=>['title'=>'De buurt bloeit op','teaser'=>'Samen groen','body'=>['Vrijwilligers planten samen bloemen in hun buurt.']],'en'=>['title'=>'The neighbourhood blooms','teaser'=>'Growing together','body'=>['Volunteers plant flowers in their neighbourhood.']]];
  Http::fake(['generativelanguage.googleapis.com/*'=>Http::sequence()->push([],503)->push([],503)->push(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode($data)]]]]]])]);
  $notices=[];app(NewsAssessmentRetry::class)->assess(app(NewsImporter::class),$a,true,function($m)use(&$notices){$notices[]=$m;});
  Http::assertSentCount(3);Sleep::assertSleptTimes(72);$this->assertStringContainsString('60 seconden',$notices[0]);$this->assertStringContainsString('300 seconden',$notices[1]);$this->assertSame(1,$a->fresh()->attempts);$this->assertSame('replacement',$a->fresh()->status);$this->assertDatabaseCount('articles',1);
 }
 public function test_longer_outage_keeps_retrying_every_five_minutes():void {
  $a=$this->article();$calls=0;$importer=$this->mock(NewsImporter::class,function($m)use(&$calls){$m->shouldReceive('assess')->times(4)->andReturnUsing(function()use(&$calls){if(++$calls<4)throw new GeminiTemporaryException('Gemini HTTP 503.');});});
  app(NewsAssessmentRetry::class)->assess($importer,$a,true);Sleep::assertSleptTimes(132);$this->assertSame(4,$calls);
 }
 public function test_permanent_errors_and_quota_do_not_enter_endless_retry():void {
  $a=$this->article();$sequence=Http::sequence();foreach([400,401,403,404,429] as $status)$sequence->push([],$status);Http::fake(['generativelanguage.googleapis.com/*'=>$sequence]);
  foreach([400,401,403,404,429] as $status){try{app(NewsAssessmentRetry::class)->assess(app(NewsImporter::class),$a,true);$this->fail('Expected a permanent error');}catch(\RuntimeException $e){$this->assertNotInstanceOf(GeminiTemporaryException::class,$e);$this->assertStringContainsString((string)$status,$e->getMessage());}}
  Sleep::assertNeverSlept();
 }
 public function test_connection_timeout_is_temporary_without_spending_content_attempt():void {
  $a=$this->article();Http::fake(fn()=>throw new \Illuminate\Http\Client\ConnectionException('timeout'));
  try{app(NewsImporter::class)->assess($a,true);$this->fail('Expected temporary error');}catch(GeminiTemporaryException $e){$this->assertSame(0,$a->fresh()->attempts);$this->assertSame('pending',$a->fresh()->status);}
 }
}
