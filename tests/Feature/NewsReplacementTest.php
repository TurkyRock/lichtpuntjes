<?php
namespace Tests\Feature;
use App\Models\Article;
use App\Services\{LichtpuntIllustrationPrompt,NewsImporter};
use Illuminate\Support\Facades\{DB,File,Http};
use Tests\TestCase;
class NewsReplacementTest extends TestCase {
 private array $files=[];private string $state;
 protected function setUp():void {parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','news_images.assessment_delay_ms'=>0,'news_images.provider'=>'pollinations','news_images.checkpoint'=>'sd_xl_base_1.0.safetensors']);DB::purge('sqlite');$this->artisan('migrate',['--force'=>true])->assertExitCode(0);$this->state=storage_path('framework/testing-replacement.json');File::delete($this->state);config(['news_images.replacement_state_file'=>$this->state]);Http::preventStrayRequests();}
 protected function tearDown():void {foreach($this->files as $f)File::delete($f);File::delete($this->state);parent::tearDown();}
 private function article(string $tag,string $status='published'):Article {return Article::create(['fingerprint'=>hash('sha256',$tag),'source_title'=>'Positive news '.$tag,'source_description'=>str_repeat('Good information. ',10),'source_name'=>'News','source_url'=>'https://example.com/'.$tag,'status'=>$status,'category'=>'sport','nl'=>['title'=>'Goed '.$tag,'teaser'=>'Mooi','body'=>['Verhaal']],'en'=>['title'=>'Good '.$tag,'teaser'=>'Happy','body'=>['Story']]]);}
 private function prepare100(Article $old):array {
  $ids=[];File::ensureDirectoryExists(public_path('news-images'));$bytes=File::get(public_path('images/lichtpunt-zon.png'));
  for($i=0;$i<100;$i++){$a=$this->article('new-'.$i,'replacement');$content=$bytes.'test-'.$i;$hash=hash('sha256',$content);$path=public_path('news-images/'.$hash.'.png');File::put($path,$content);$this->files[]=$path;$a->update(['photo'=>['url'=>'/news-images/'.$hash.'.png','sha256'=>$hash,'generated'=>true,'model'=>'black-forest-labs/flux.1-schnell','style'=>LichtpuntIllustrationPrompt::STYLE,'subject_hash'=>hash('sha256',app(LichtpuntIllustrationPrompt::class)->positive($a))]]);$ids[]=$a->id;}
  File::put($this->state,json_encode(['old_ids'=>[$old->id],'new_ids'=>$ids,'cursor'=>null,'exhausted'=>true,'finished'=>false]));return $ids;
 }
 public function test_incomplete_collection_never_deletes_existing_news():void {$old=$this->article('old');$this->mock(NewsImporter::class,fn($m)=>$m->shouldReceive('fetchPage')->once()->andReturn(['created'=>[],'next'=>null]));$this->artisan('news:replace',['--pages'=>1])->expectsOutputToContain('0/100')->assertExitCode(0);$this->assertNotNull($old->fresh());$this->assertSame('published',$old->fresh()->status);}
 public function test_staging_approval_is_never_public_before_replacement():void {
  $a=$this->article('pending','pending');config(['news.gemini_key'=>'test','news.gemini_model'=>'test-model']);$text=['title'=>'Goed nieuws','teaser'=>'Een mooie ontwikkeling','body'=>['Een betrouwbare samenvatting.']];$result=['decision'=>'suitable','reason'=>'Positief','category'=>'people','nl'=>$text,'en'=>$text];Http::fake(['generativelanguage.googleapis.com/*'=>Http::response(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode($result)]]]]]])]);app(NewsImporter::class)->assess($a,true);$this->assertSame('replacement',$a->fresh()->status);$this->assertSame(0,Article::where('status','published')->count());
 }
 public function test_backup_failure_never_publishes_or_deletes_any_article():void {
  $old=$this->article('old');$this->prepare100($old);
  File::partialMock()->shouldReceive('put')->withArgs(fn($path,$contents)=>str_contains($path,'news-replacement-backups'))->andReturn(false);
  $this->artisan('news:replace')->expectsOutputToContain('Herstelkopie opslaan mislukt')->assertExitCode(1);
  $this->assertNotNull($old->fresh());$this->assertSame(1,Article::where('status','published')->count());$this->assertSame(100,Article::where('status','replacement')->count());
 }
 public function test_complete_collection_replaces_old_news_once_and_writes_backup():void {
  $old=$this->article('old');$community=$this->article('community','community_pending');$community->update(['origin'=>'community']);$ids=$this->prepare100($old);$before=File::glob(storage_path('app/news-replacement-backups/*.json'));$this->mock(NewsImporter::class,fn($m)=>$m->shouldNotReceive('fetchPage'));
  try{$this->artisan('news:replace')->expectsOutputToContain('100 nieuwe positieve artikelen')->assertExitCode(0);$this->assertNull($old->fresh());$this->assertSame(100,Article::where('status','published')->count());$this->assertSame(101,Article::count());$this->assertNotNull($community->fresh());$backup=array_values(array_diff(File::glob(storage_path('app/news-replacement-backups/*.json')),$before));$this->assertCount(1,$backup);$data=json_decode(File::get($backup[0]),true);$this->assertSame($old->id,$data['tables']['articles'][0]['id']);$this->artisan('news:replace')->expectsOutputToContain('niets opnieuw verwijderd')->assertExitCode(0);$this->assertSame(101,Article::count());$this->assertNotNull($community->fresh());}
  finally{foreach(array_diff(File::glob(storage_path('app/news-replacement-backups/*.json')),$before) as $f)$this->files[]=$f;}
 }
}
