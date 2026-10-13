<?php
namespace Tests\Feature;
use App\Models\Article;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;
class NewsPaginationTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        $this->artisan('migrate',['--force'=>true])->assertExitCode(0);
    }
    private function story(array $extra=[]): Article {
        $id=(string)Str::uuid();
        return Article::create(array_merge(['fingerprint'=>hash('sha256',$id),'source_title'=>'Source story',
            'source_description'=>'Description','source_name'=>'Test source','source_url'=>'https://example.org/'.$id,
            'status'=>'published','category'=>'media','published_at'=>'2026-10-10 07:00:00',
            'nl'=>['title'=>'Goed nieuws','teaser'=>'Een lichtpuntje','body'=>['Een mooi verhaal.']],
            'en'=>['title'=>'Good news','teaser'=>'A bright spot','body'=>['A happy story.']]],$extra));
    }
    public function test_pages_have_twelve_stories_and_no_overlap(): void {
        for($i=0;$i<25;$i++)$this->story();
        $this->story(['status'=>'draft']);
        $one=$this->getJson('/news/feed?page=1')->assertOk()->assertJsonCount(12,'articles')
            ->assertJsonPath('pagination.total',25)->assertJsonPath('pagination.last_page',3)->json('articles');
        $two=$this->getJson('/news/feed?page=2')->assertOk()->assertJsonCount(12,'articles')->json('articles');
        $this->assertEmpty(array_intersect(array_column($one,'id'),array_column($two,'id')));
        $this->getJson('/news/feed?page=3')->assertOk()->assertJsonCount(1,'articles');
        $this->getJson('/news/feed?page=999')->assertOk()->assertJsonPath('pagination.current_page',3)->assertJsonCount(1,'articles');
    }
    public function test_stories_beyond_previous_150_limit_remain_accessible(): void {
        for($i=0;$i<161;$i++)$this->story();
        $this->getJson('/news/feed?page=14')->assertOk()->assertJsonPath('pagination.total',161)->assertJsonCount(5,'articles');
    }
    public function test_category_and_search_apply_before_pagination(): void {
        for($i=0;$i<14;$i++)$this->story(['category'=>'sport']);
        $target=$this->story(['nl'=>['title'=>'Bijzonder concert','teaser'=>'Goed nieuws','body'=>['Vrijwilligers helpen mee.']],
            'en'=>['title'=>'A special concert','teaser'=>'Good news','body'=>['Volunteers help out.']]]);
        $this->story(['category'=>'sport','nl'=>$target->nl,'en'=>$target->en]);
        $this->getJson('/news/feed?category=media&q=vrijwilligers&lang=nl')->assertOk()
            ->assertJsonPath('pagination.total',1)->assertJsonPath('articles.0.id',(string)$target->id);
        $this->getJson('/news/feed?category=media&q=volunteers&lang=en')->assertOk()->assertJsonPath('pagination.total',1);
        $this->getJson('/news/feed?category=media&q=unmatched')->assertOk()->assertJsonCount(0,'articles')->assertJsonPath('pagination.total',0);
    }
    public function test_invalid_page_and_category_are_rejected(): void {
        $this->getJson('/news/feed?page=0')->assertUnprocessable();
        $this->getJson('/news/feed?category=unknown')->assertUnprocessable();
    }
}
