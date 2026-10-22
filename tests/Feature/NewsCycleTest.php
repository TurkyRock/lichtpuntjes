<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{Article,User,LightspotSubmission};
use App\Services\{DailyIllustrationBudget,ImageBudgetException,NewsServiceException,NewsImporter,ArticlePhotoFinder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache,File};
use Illuminate\Support\Sleep;
use Illuminate\Console\Scheduling\Schedule;

class NewsCycleTest extends TestCase
{
    use RefreshDatabase;
    private string $path;
    protected function setUp(): void {
        parent::setUp();$this->path=sys_get_temp_dir().'/lichtpunt-budget-'.bin2hex(random_bytes(8)).'.json';
        config(['cache.default'=>'array','news_cycle.image_budget_enabled'=>true,'news_cycle.budget_file'=>$this->path]);Sleep::fake();
    }
    protected function tearDown(): void {File::delete([$this->path,$this->path.'.tmp']);$this->travelBack();parent::tearDown();}
    private function article(array $extra=[]): Article {
        $tag=bin2hex(random_bytes(8));
        return Article::create($extra+['fingerprint'=>hash('sha256',random_bytes(16)),'source_title'=>'Good news '.$tag,'source_description'=>str_repeat('Happy news. ',20),'source_name'=>'Source','source_url'=>'https://example.com','origin'=>'news','status'=>'pending','category'=>'sport','published_at'=>now(),'nl'=>['title'=>'Mooi '.$tag],'en'=>['title'=>'Lovely '.$tag]]);
    }
    public function test_separate_budgets_reserve_space_for_user_stories_and_reset(): void {
        config(['news_cycle.news_images'=>1,'news_cycle.community_images'=>1]);$budget=app(DailyIllustrationBudget::class);
        $budget->reserve($this->article());
        try{$budget->reserve($this->article());$this->fail('Expected news cap');}catch(ImageBudgetException $e){$this->assertStringContainsString('nieuwsillustraties',$e->getMessage());}
        $budget->reserve($this->article(['origin'=>'community']));
        $state=json_decode(File::get($this->path),true);$this->assertSame(1,$state['news']);$this->assertSame(1,$state['community']);
        $this->travel(1)->days();$budget->reserve($this->article());$this->assertSame(0,json_decode(File::get($this->path),true)['community']);
    }
    public function test_provider_exhaustion_prevents_new_reservations_until_reset(): void {
        $budget=app(DailyIllustrationBudget::class);$budget->exhausted();
        try{$budget->reserve($this->article());$this->fail('Expected provider cap');}catch(ImageBudgetException $e){$this->assertGreaterThanOrEqual(60,$e->retryAfter());}
        $this->assertFileDoesNotExist($this->path);
        $this->travel(1)->days();$budget->reserve($this->article());$this->assertFileExists($this->path);
    }
    public function test_corrupt_budget_fails_closed(): void {
        File::put($this->path,'broken');$this->expectException(NewsServiceException::class);app(DailyIllustrationBudget::class)->reserve($this->article());
    }
    public function test_older_approved_story_gets_priority(): void {
        $u=User::forceCreate(['name'=>'Author','email'=>'writer@example.com','password'=>'TestPassword123','email_verified_at'=>now(),'community_accepted_at'=>now()]);
        $a=$this->article(['origin'=>'community','author_id'=>$u->id]);
        LightspotSubmission::create(['user_id'=>$u->id,'article_id'=>$a->id,'title'=>'Story','body'=>'Story text','language'=>'nl','category'=>'community','status'=>'image_wait']);
        try{app(DailyIllustrationBudget::class)->reserve($this->article(['origin'=>'community']));$this->fail('Expected priority check');}catch(NewsServiceException $e){$this->assertStringContainsString('eerder goedgekeurd',$e->getMessage());}
        app(DailyIllustrationBudget::class)->reserve($a);$this->assertSame(1,json_decode(File::get($this->path),true)['community']);
    }
    public function test_cycle_assesses_at_most_30_and_publishes_six_varied_stories(): void {
        foreach(['media','sport','energy','nature','science','people'] as $cat)for($i=0;$i<6;$i++)$this->article(['category'=>$cat]);
        $this->mock(NewsImporter::class,function($m){$m->shouldReceive('fetch')->once()->with(3)->andReturn(30);$m->shouldReceive('assess')->times(30)->withArgs(function($a,$stage,$status){$this->assertTrue($stage);$this->assertSame('news_ready',$status);$a->update(['status'=>'news_ready']);return true;});});
        $this->mock(ArticlePhotoFinder::class,function($m){$m->shouldReceive('attach')->times(5)->andReturn(true);$m->shouldReceive('current')->times(5)->andReturn(true);});
        $this->artisan('news:cycle')->assertExitCode(0);
        $this->assertSame(5,Article::where('status','published')->count());
        $this->assertSame(5,Article::where('status','published')->distinct()->count('category'));
        $this->assertSame(6,Article::where('status','pending')->count());
    }
    public function test_image_failure_keeps_approved_news_private(): void {
        $a=$this->article(['status'=>'news_ready']);
        $this->mock(NewsImporter::class,fn($m)=>$m->shouldReceive('fetch')->once()->with(3)->andReturn(0));
        $this->mock(ArticlePhotoFinder::class,fn($m)=>$m->shouldReceive('attach')->once()->andThrow(new ImageBudgetException('AI-puntjes: op.')));
        $this->artisan('news:cycle')->assertExitCode(0);$this->assertSame('news_ready',$a->fresh()->status);$this->getJson('/news/feed')->assertJsonPath('pagination.total',0);
    }
    public function test_schedule_uses_five_daytime_rounds_in_amsterdam(): void {
        $events=app(Schedule::class)->events();$cycle=collect($events)->first(fn($e)=>str_contains($e->command??'','news:cycle'));
        $this->assertNotNull($cycle);$this->assertSame('0 7,10,13,16,19 * * *',$cycle->expression);$this->assertSame('Europe/Amsterdam',$cycle->timezone);
        $this->assertTrue($cycle->withoutOverlapping);
    }
}
