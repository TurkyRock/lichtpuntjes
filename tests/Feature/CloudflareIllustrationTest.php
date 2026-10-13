<?php
namespace Tests\Feature;
use App\Models\Article;
use App\Services\{ArticleImageGenerator,ArticlePhotoFinder,CloudflareIllustrator,NewsServiceException};
use Illuminate\Support\Facades\{DB,File,Http};
use Tests\TestCase;
use Illuminate\Support\Sleep;
class CloudflareIllustrationTest extends TestCase {
 private string $counter;
 protected function setUp():void {
  parent::setUp();Sleep::fake();$this->counter=storage_path('app/test-cloudflare-'.\Illuminate\Support\Str::uuid().'.json');
  config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','news_images.provider'=>'cloudflare','news_images.cloudflare_account_id'=>str_repeat('a',32),'news_images.cloudflare_token'=>'test-cloudflare-secret','news_images.cloudflare_state_file'=>$this->counter]);
  DB::purge('sqlite');$this->artisan('migrate',['--force'=>true])->assertExitCode(0);Http::preventStrayRequests();
 }
 protected function tearDown():void {File::delete($this->counter);parent::tearDown();}
 private function article():Article {return Article::create(['fingerprint'=>hash('sha256',uniqid()),'source_title'=>'Team celebrates','source_name'=>'News','source_url'=>'https://example.com/news','status'=>'replacement','category'=>'sport','nl'=>['title'=>'Team viert feest'],'en'=>['title'=>'Team celebrates','teaser'=>str_repeat('A positive team story. ',100)]]);}
 public function test_check_lists_model_without_generating():void {
  Http::fake(['api.cloudflare.com/*'=>Http::response(['success'=>true,'result'=>[['name'=>CloudflareIllustrator::MODEL]]])]);
  $this->artisan('news:illustrations',['--check'=>true])->expectsOutputToContain('Cloudflare: account, token')->assertExitCode(0);
  Http::assertSentCount(1);Http::assertSent(fn($r)=>$r->method()==='GET'&&$r->hasHeader('Authorization','Bearer test-cloudflare-secret'));$this->assertFileDoesNotExist($this->counter);
 }
 public function test_generation_saves_unique_image_and_resumes_without_second_request():void {
  $a=$this->article();$bytes=File::get(public_path('images/lichtpunt-zon.png'));$file=public_path('news-images/'.hash('sha256',$bytes).'.png');File::delete($file);
  Http::fake(['api.cloudflare.com/*'=>Http::response(['success'=>true,'result'=>['image'=>base64_encode($bytes)]])]);
  try{
   $f=app(ArticlePhotoFinder::class);$f->attach($a);$f->attach($a);Http::assertSentCount(1);
   $this->assertSame('replacement',$a->fresh()->status);$this->assertSame(CloudflareIllustrator::MODEL,$a->fresh()->photo['model']);$this->assertTrue($f->current($a->fresh()));
   Http::assertSent(fn($r)=>$r->method()==='POST'&&strlen($r['prompt'])<=2048&&str_contains($r['prompt'],'cut-paper')&&str_contains($r['prompt'],'Team celebrates')&&$r['steps']===4&&array_keys($r->data())===['prompt','steps']&&$r->hasHeader('Content-Type','application/json')&&$r->hasHeader('Authorization','Bearer test-cloudflare-secret')&&!str_contains($r->url(),'test-cloudflare-secret'));
   $this->assertSame(1,json_decode(File::get($this->counter),true)['requests']);
  }finally{File::delete($file);}
 }
 public function test_provider_error_is_safe_and_counts_attempt_without_retry():void {
  Http::fake(['api.cloudflare.com/*'=>Http::response(['success'=>false,'errors'=>[['code'=>3036,'message'=>'test-cloudflare-secret']]],429)]);
  try{app(CloudflareIllustrator::class)->generate($this->article());$this->fail('Expected limit error');}catch(NewsServiceException $e){$this->assertStringContainsString('limiet',$e->getMessage());$this->assertStringNotContainsString('secret',$e->getMessage());}
  Http::assertSentCount(1);$this->assertSame(1,json_decode(File::get($this->counter),true)['requests']);
 }
 public function test_daily_request_cap_blocks_generation():void {
  File::put($this->counter,json_encode(['day'=>now('UTC')->format('Y-m-d'),'requests'=>100]));
  try{app(CloudflareIllustrator::class)->generate($this->article());$this->fail('Expected cap');}catch(NewsServiceException $e){$this->assertStringContainsString('100 aanvragen',$e->getMessage());}Http::assertNothingSent();
 }
 public function test_counter_resets_next_utc_day():void {
  File::put($this->counter,json_encode(['day'=>now('UTC')->subDay()->format('Y-m-d'),'requests'=>100]));Http::fake(['api.cloudflare.com/*'=>Http::response(['success'=>false,'errors'=>[['code'=>3036]]],429)]);
  try{app(CloudflareIllustrator::class)->generate($this->article());}catch(NewsServiceException $e){}$this->assertSame(1,json_decode(File::get($this->counter),true)['requests']);Http::assertSentCount(1);
 }
 public function test_corrupt_counter_fails_closed():void {
  File::put($this->counter,'broken');try{app(CloudflareIllustrator::class)->generate($this->article());$this->fail('Expected corrupt counter');}catch(NewsServiceException $e){$this->assertStringContainsString('beschadigd',$e->getMessage());}Http::assertNothingSent();
 }
 public function test_missing_token_does_not_contact_provider():void {
  config(['news_images.cloudflare_token'=>'']);try{app(ArticleImageGenerator::class)->check();$this->fail('Expected credentials error');}catch(NewsServiceException $e){$this->assertStringContainsString('CLOUDFLARE_API_TOKEN',$e->getMessage());}Http::assertNothingSent();
 }
 public function test_bad_request_exposes_provider_code_and_safe_message_without_retry():void {
  Http::fake(['api.cloudflare.com/*'=>Http::response(['success'=>false,'errors'=>[['code'=>3003,'message'=>"Missing headers or body. Bearer test-cloudflare-secret Account ".str_repeat('a',32)."\n"]]],400)]);
  try{app(CloudflareIllustrator::class)->generate($this->article());$this->fail('Expected error');}catch(NewsServiceException $e){$this->assertStringContainsString('code 3003',$e->getMessage());$this->assertStringContainsString('Missing headers or body',$e->getMessage());$this->assertStringNotContainsString('test-cloudflare-secret',$e->getMessage());$this->assertStringNotContainsString(str_repeat('a',32),$e->getMessage());$this->assertStringNotContainsString("\n",$e->getMessage());}Http::assertSentCount(1);
 }
 public function test_temporary_failure_retries_and_saves_image():void {
  $bytes=File::get(public_path('images/lichtpunt-zon.png'));$file=public_path('news-images/'.hash('sha256',$bytes).'.png');File::delete($file);
  Http::fake(['api.cloudflare.com/*'=>Http::sequence()->push(['success'=>false,'errors'=>[['code'=>8007,'message'=>'Cog prediction failed']]],409)->push(['success'=>false],503)->push(['success'=>true,'result'=>['image'=>base64_encode($bytes)]])]);
  try{app(ArticlePhotoFinder::class)->attach($this->article());Http::assertSentCount(3);$this->assertSame(3,json_decode(File::get($this->counter),true)['requests']);Sleep::assertSequence([Sleep::for(10)->seconds(),Sleep::for(20)->seconds(),Sleep::for(40)->seconds()]);}finally{File::delete($file);}
 }
 public function test_retries_stop_after_three_extra_attempts():void {
  Http::fake(['api.cloudflare.com/*'=>Http::response(['success'=>false,'errors'=>[['code'=>8007,'message'=>'Cog prediction failed']]],409)]);
  try{app(CloudflareIllustrator::class)->generate($this->article());$this->fail('Expected failure');}catch(NewsServiceException $e){$this->assertStringContainsString('8007',$e->getMessage());}
  Http::assertSentCount(4);$this->assertSame(4,json_decode(File::get($this->counter),true)['requests']);Sleep::assertSequence([Sleep::for(10)->seconds(),Sleep::for(20)->seconds(),Sleep::for(40)->seconds(),Sleep::for(60)->seconds()]);
 }
 public function test_retry_cannot_exceed_daily_request_cap():void {
  File::put($this->counter,json_encode(['day'=>now('UTC')->format('Y-m-d'),'requests'=>99]));Http::fake(['api.cloudflare.com/*'=>Http::response(['success'=>false],409)]);
  try{app(CloudflareIllustrator::class)->generate($this->article());$this->fail('Expected cap');}catch(NewsServiceException $e){$this->assertStringContainsString('100 aanvragen',$e->getMessage());}Http::assertSentCount(1);$this->assertSame(100,json_decode(File::get($this->counter),true)['requests']);
 }
 public function test_long_retry_after_stops_instead_of_retrying_early():void {
  Http::fake(['api.cloudflare.com/*'=>Http::response(['success'=>false],429,['Retry-After'=>'120'])]);
  try{app(CloudflareIllustrator::class)->generate($this->article());$this->fail('Expected limit');}catch(NewsServiceException $e){}Http::assertSentCount(1);Sleep::assertSequence([Sleep::for(10)->seconds()]);
 }
 public function test_authentication_error_is_not_retried():void {
  Http::fake(['api.cloudflare.com/*'=>Http::response(['success'=>false],403)]);
  try{app(CloudflareIllustrator::class)->generate($this->article());$this->fail('Expected permissions error');}catch(NewsServiceException $e){$this->assertStringContainsString('Read/Edit',$e->getMessage());}Http::assertSentCount(1);Sleep::assertSequence([Sleep::for(10)->seconds()]);
 }
}
