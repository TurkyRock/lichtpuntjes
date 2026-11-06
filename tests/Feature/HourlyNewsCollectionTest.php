<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\Article;
use App\Services\{ArticlePhotoFinder,GeminiTemporaryException,ImageBudgetException,NewsCategoryBalance,NewsDuplicates,NewsImporter};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache,File,Http};
use Illuminate\Support\Sleep;

class HourlyNewsCollectionTest extends TestCase
{
    use RefreshDatabase;
    private string $folder;
    protected function setUp():void {
        parent::setUp();$this->travelTo(\Carbon\Carbon::parse('2026-10-29 12:00:00','UTC'));
        $this->folder=storage_path('framework/testing/hourly-'.bin2hex(random_bytes(6)));
        config(['news_hourly.state_file'=>$this->folder.'/state.json','news_hourly.assessment_delay'=>0]);
        Sleep::fake();Http::preventStrayRequests();
        $this->mock(NewsDuplicates::class,fn($m)=>$m->shouldReceive('hideIfDuplicate')->andReturn(false));
    }
    protected function tearDown():void {File::deleteDirectory($this->folder);$this->travelBack();parent::tearDown();}
    private function story(string $cat,string $status='news_ready'):Article {
        $tag=bin2hex(random_bytes(8));
        return Article::create(['fingerprint'=>hash('sha256',$tag),'origin'=>'news','status'=>$status,'source_title'=>'Hoopvol '.$tag,'source_description'=>str_repeat('Veel positief nieuws. ',20),'source_name'=>'Bron','source_url'=>'https://example.test/'.$tag,'category'=>$cat,'published_at'=>now(),'nl'=>['title'=>'Hoopvol '.$tag,'teaser'=>'Een mooie ontwikkeling.','body'=>['Een volledig positief verhaal.']],'en'=>['title'=>'Hopeful '.$tag,'teaser'=>'A lovely development.','body'=>['An uplifting story.']]]);
    }
    private function ready(int $per=3):void {foreach(NewsCategoryBalance::CATEGORIES as $cat)for($i=0;$i<$per;$i++)$this->story($cat);}
    private function photos(int $count):void {$this->mock(ArticlePhotoFinder::class,function($m)use($count){$m->shouldReceive('attach')->times($count)->andReturn(true);$m->shouldReceive('current')->times($count)->andReturn(true);});}
    private function assertThreeNew(array $old=[]):void {
        foreach(NewsCategoryBalance::CATEGORIES as $cat)$this->assertSame(3,Article::where('status','published')->where('category',$cat)->whereNotIn('id',$old)->count());
        Http::assertNothingSent();
    }
    public function test_adds_three_per_category_despite_an_unbalanced_existing_collection_and_is_idempotent():void {
        $old=[];for($i=0;$i<20;$i++)$old[]=$this->story('sport','published')->id;
        $this->ready();$this->photos(18);$this->mock(NewsImporter::class,fn($m)=>$m->shouldNotReceive('fetchPage'));
        $this->artisan('news:hourly',['--per-category'=>3])->assertSuccessful();$this->assertThreeNew($old);
        $this->artisan('news:hourly',['--per-category'=>3])->expectsOutput('Deze uurronde is al afgerond. Er worden geen extra artikelen geplaatst.')->assertSuccessful();
        $this->assertDatabaseCount('articles',38);
    }
    public function test_next_hour_creates_a_new_batch_and_keeps_the_previous_news():void {
        $this->ready();$this->photos(36);$this->mock(NewsImporter::class,fn($m)=>$m->shouldNotReceive('fetchPage'));
        $this->artisan('news:hourly')->assertSuccessful();$old=Article::where('status','published')->pluck('id')->all();
        $this->travel(1)->hours();$this->ready();$this->artisan('news:hourly')->assertSuccessful();$this->assertThreeNew($old);$this->assertDatabaseCount('articles',36);
    }
    public function test_fetches_past_rejections_and_uses_actual_ai_categories():void {
        $this->ready(2);$this->photos(18);
        $this->mock(NewsImporter::class,function($m){
            $m->shouldReceive('fetchPage')->times(6)->andReturnUsing(function($cursor,$cat){
                $good=$this->story($cat,'pending');$bad=$this->story($cat,'pending');$bad->update(['source_name'=>'Afwijzen']);return ['created'=>[$bad->id,$good->id],'next'=>null];
            });
            $m->shouldReceive('assess')->times(12)->withArgs(function($article,$stage,$status){
                $this->assertTrue($stage);$this->assertSame('news_ready',$status);
                $existing=$article->source_name==='Afwijzen';
                // Each fetched pair contains one suitable and one rejected story.
                $article->update(['status'=>$existing?'rejected':'news_ready']);return true;
            });
        });
        $this->artisan('news:hourly')->assertSuccessful();$this->assertThreeNew();$this->assertSame(6,Article::where('status','rejected')->count());
    }
    public function test_budget_pause_resumes_the_same_batch_next_hour_without_double_counting():void {
        $this->ready();$calls=0;
        $this->mock(ArticlePhotoFinder::class,function($m)use(&$calls){$m->shouldReceive('attach')->times(19)->andReturnUsing(function()use(&$calls){if(++$calls===3)throw new ImageBudgetException('Dagbudget op.');return true;});$m->shouldReceive('current')->times(18)->andReturn(true);});
        $this->mock(NewsImporter::class,fn($m)=>$m->shouldNotReceive('fetchPage'));
        $this->artisan('news:hourly')->assertExitCode(2);$this->assertSame(2,Article::where('status','published')->count());
        $this->travel(1)->hours();$this->artisan('news:hourly')->assertSuccessful();$this->assertThreeNew();$this->assertDatabaseCount('articles',18);
    }
    public function test_the_real_daily_image_budget_is_not_bypassed():void {
        config(['news_cycle.image_budget_enabled'=>true,'news_cycle.news_images'=>30,'news_cycle.budget_file'=>$this->folder.'/budget.json']);
        $this->ready();
        $this->mock(ArticlePhotoFinder::class,function($m){$m->shouldReceive('attach')->times(31)->andReturnUsing(function($a){$this->assertSame('lichtpunt-photo-v1',$a->photo['requested_style']);app(\App\Services\DailyIllustrationBudget::class)->reserve($a);return true;});$m->shouldReceive('current')->times(30)->andReturn(true);});
        $this->mock(NewsImporter::class,fn($m)=>$m->shouldNotReceive('fetchPage'));
        $this->artisan('news:hourly')->assertSuccessful();$this->travel(1)->hours();$this->ready();
        $this->artisan('news:hourly')->assertExitCode(2);$this->assertSame(30,Article::where('status','published')->count());
        $this->assertSame(6,Article::where('status','news_ready')->count());
    }
    public function test_crash_after_publication_is_reconciled_from_the_previously_saved_selection():void {
        $ids=[];for($i=0;$i<3;$i++)$ids[$this->story('nature','published')->id]='nature';
        foreach(array_diff(NewsCategoryBalance::CATEGORIES,['nature']) as $cat)for($i=0;$i<3;$i++)$this->story($cat);
        File::ensureDirectoryExists($this->folder);File::put($this->folder.'/state.json',json_encode(['version'=>1,'hour'=>now('UTC')->format('Y-m-d-H'),'target'=>3,'selected'=>$ids,'finished'=>false]));
        $this->photos(15);$this->mock(NewsImporter::class,fn($m)=>$m->shouldNotReceive('fetchPage'));
        $this->artisan('news:hourly')->assertSuccessful();$this->assertThreeNew();
    }
    public function test_exhausted_sources_do_not_lower_the_quality_bar_or_loop_forever():void {
        $this->mock(NewsImporter::class,fn($m)=>$m->shouldReceive('fetchPage')->times(6)->andReturn(['created'=>[],'next'=>null]));
        $this->mock(ArticlePhotoFinder::class,fn($m)=>$m->shouldNotReceive('attach'));
        $this->artisan('news:hourly')->assertExitCode(2);$this->assertDatabaseCount('articles',0);
    }
    public function test_temporary_gemini_failure_retries_the_same_candidate_and_stages_it_privately():void {
        foreach(NewsCategoryBalance::CATEGORIES as $cat)for($i=0;$i<($cat==='nature'?2:3);$i++)$this->story($cat);
        $pending=$this->story('nature','pending');$this->photos(18);$attempts=0;
        $this->mock(NewsImporter::class,function($m)use(&$attempts,$pending){$m->shouldReceive('assess')->twice()->withArgs(fn($a,$stage,$status)=>$a->id===$pending->id&&$stage===true&&$status==='news_ready')->andReturnUsing(function($a)use(&$attempts){if(++$attempts===1)throw new GeminiTemporaryException('Gemini HTTP 503.');$a->update(['status'=>'news_ready']);});$m->shouldNotReceive('fetchPage');});
        $this->artisan('news:hourly')->assertSuccessful();$this->assertThreeNew();Sleep::assertSleptTimes(12);
    }
    public function test_reclassified_candidates_count_towards_the_actual_category():void {
        foreach(NewsCategoryBalance::CATEGORIES as $cat)for($i=0;$i<($cat==='nature'||$cat==='sport'?2:3);$i++)$this->story($cat);
        $this->story('nature','pending');$this->photos(18);
        $this->mock(NewsImporter::class,function($m){
            $m->shouldReceive('assess')->once()->andReturnUsing(fn($a)=>$a->update(['category'=>'sport','status'=>'news_ready']));
            $m->shouldReceive('fetchPage')->once()->with(null,'nature')->andReturnUsing(function(){ $a=$this->story('nature');return ['created'=>[$a->id],'next'=>null];});
        });
        $this->artisan('news:hourly')->assertSuccessful();$this->assertThreeNew();
    }
    public function test_hourly_scheduler_replaces_the_old_daytime_cycle_only_when_enabled():void {
        config(['news_hourly.enabled'=>true]);
        $schedule=new \Illuminate\Console\Scheduling\Schedule('UTC');
        \Illuminate\Support\Facades\Schedule::swap($schedule);
        require base_path('routes/console.php');
        $events=collect($schedule->events());
        $event=$events->first(fn($e)=>str_contains($e->command??'','news:hourly'));
        $this->assertNotNull($event);$this->assertStringContainsString('-d memory_limit=512M',$event->command);$this->assertSame('0 * * * *',$event->expression);$this->assertTrue($event->withoutOverlapping);
        $this->assertFalse($events->contains(fn($e)=>str_contains($e->command??'','news:cycle')));
    }
    public function test_another_import_is_not_interrupted():void {
        $lock=Cache::lock('lichtpunt-import',600);$this->assertTrue($lock->get());
        $this->mock(NewsImporter::class,fn($m)=>$m->shouldNotReceive('fetchPage'));
        $this->artisan('news:hourly')->assertSuccessful();$this->assertFileDoesNotExist($this->folder.'/state.json');$lock->release();
    }
    public function test_invalid_or_corrupt_progress_fails_closed():void {
        File::ensureDirectoryExists($this->folder);File::put($this->folder.'/state.json','invalid');
        $this->mock(NewsImporter::class,fn($m)=>$m->shouldNotReceive('fetchPage'));
        $this->artisan('news:hourly')->assertFailed();$this->assertDatabaseCount('articles',0);
        $this->artisan('news:hourly',['--per-category'=>'0'])->assertExitCode(2);
    }
}
