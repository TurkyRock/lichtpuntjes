<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\Article;
use App\Services\{NewsCategoryBalance,NewsImporter,ArticlePhotoFinder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
class NewsCategoryBalanceTest extends TestCase {
 use RefreshDatabase;
 private function article(string $category,string $status='published',array $extra=[]):Article {
  return Article::create($extra+['fingerprint'=>bin2hex(random_bytes(16)),'source_title'=>'Happy story','source_url'=>'https://example.com/story','source_name'=>'Source','origin'=>'news','category'=>$category,'status'=>$status,'published_at'=>now()]);
 }
 public function test_cycle_fills_nature_and_does_not_publish_surplus_sport():void {
  config(['cache.default'=>'array']);Sleep::fake();
  foreach(NewsCategoryBalance::CATEGORIES as $cat)if($cat!=='nature')$this->article($cat);
  for($i=0;$i<8;$i++)$this->article('sport');
  $nature=$this->article('nature','news_ready');$sport=$this->article('sport','news_ready');
  $this->mock(NewsImporter::class,fn($m)=>$m->shouldReceive('fetch')->once()->andReturn(0));
  $this->mock(ArticlePhotoFinder::class,function($m)use($nature){$m->shouldReceive('attach')->once()->withArgs(fn($a)=>$a->id===$nature->id)->andReturn(true);$m->shouldReceive('current')->once()->andReturn(true);});
  $this->artisan('news:cycle')->assertExitCode(0);
  $this->assertSame('published',$nature->fresh()->status);$this->assertSame('news_ready',$sport->fresh()->status);
 }
 public function test_fetch_targets_missing_categories_and_keeps_cursors_scoped_to_their_query():void {
  config(['news.newsdata_key'=>'testing']);
  foreach(['sport','media','science','people'] as $cat)for($i=0;$i<30;$i++)$this->article($cat);
  Http::fake(function($r){$nature=str_contains($r['q']??'','wildlife');$page=$r['page']??'first';return Http::response(['status'=>'success','results'=>[['title'=>'Happy','description'=>'Source','link'=>'https://example.org/'.($nature?'nature':'energy').'/'.$page]],'nextPage'=>$nature?'nature-cursor':'energy-cursor']);});
  app(NewsImporter::class)->fetch(3);
  Http::assertSentCount(3);
  Http::assertSent(fn($r)=>$r['category']==='environment'&&str_contains($r['q'],'wildlife'));
  Http::assertSent(fn($r)=>$r['category']==='environment'&&str_contains($r['q'],'renewable'));
  Http::assertSent(fn($r)=>($r['page']??null)==='nature-cursor'&&str_contains($r['q'],'wildlife'));
  Http::assertNotSent(fn($r)=>$r['category']==='sports');
 }
 public function test_hidden_and_user_stories_do_not_distort_news_counts():void {
  $this->article('nature','published',['origin'=>'community']);$this->article('nature','published',['hidden_at'=>now()]);$this->article('sport');
  $counts=app(NewsCategoryBalance::class)->counts();$this->assertSame(0,$counts['nature']);$this->assertSame(1,$counts['sport']);$this->assertFalse(app(NewsCategoryBalance::class)->allowed('sport'));
 }
 public function test_new_replacement_batches_select_16_or_17_per_category():void {
  $ids=[];
  foreach(NewsCategoryBalance::CATEGORIES as $cat)for($i=0;$i<($cat==='sport'?40:17);$i++)$ids[]=$this->article($cat,'replacement')->id;
  $command=new \App\Console\Commands\ReplaceNewsCollection();
  $state=new \ReflectionProperty($command,'state');$state->setValue($command,['new_ids'=>$ids,'category_balanced'=>true]);
  $method=new \ReflectionMethod($command,'ready');$ready=$method->invoke($command);
  $this->assertCount(100,$ready);$counts=$ready->groupBy('category')->map->count()->all();
  $this->assertSame(16,$counts['sport']);$this->assertSame(17,$counts['nature']);$this->assertLessThanOrEqual(1,max($counts)-min($counts));
 }

}
