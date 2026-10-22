<?php
namespace Tests\Feature;

use App\Models\Article;
use App\Services\{NewsDuplicates, NewsImporter};
use Illuminate\Support\Facades\{DB, Http};
use Tests\TestCase;

class NewsDuplicatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:', 'cache.default'=>'array']);
        DB::purge('sqlite');
        $this->artisan('migrate', ['--force'=>true])->assertExitCode(0);
        Http::preventStrayRequests();
    }

    private function article(string $title, array $extra=[]): Article
    {
        return Article::create($extra + ['fingerprint'=>bin2hex(random_bytes(32)), 'origin'=>'news',
            'source_title'=>$title, 'source_name'=>'Bron', 'source_url'=>'https://example.com/'.bin2hex(random_bytes(8)),
            'source_description'=>str_repeat('Reliable positive news. ',10), 'status'=>'published', 'category'=>'nature',
            'nl'=>['title'=>$title,'teaser'=>'Kort stukje','body'=>['Eerste alinea.','Tweede alinea.']],
            'en'=>['title'=>$title,'teaser'=>'Short text','body'=>['First paragraph.','Last paragraph.']]]);
    }

    public function test_import_skips_same_title_across_sources_and_within_one_response(): void
    {
        config(['news.newsdata_key'=>'test']);
        $this->article('Bijen krijgen meer ruimte');
        Http::fake(['newsdata.io/*'=>Http::response(['status'=>'success','results'=>[
            ['title'=>' BIJEN krijgen meer ruimte! ','link'=>'https://second.example/story'],
            ['title'=>'Nieuwe zonne-energie voor 10 scholen','link'=>'https://third.example/story'],
            ['title'=>'Nieuwe zonne energie voor 10 scholen.','link'=>'https://fourth.example/story'],
            ['title'=>'Nieuwe zonne-energie voor 20 scholen','link'=>'https://fifth.example/story'],
        ]])]);
        $result=app(NewsImporter::class)->fetchPage();
        $this->assertCount(2,$result['created']); $this->assertSame(3,Article::count());
        Http::assertSentCount(1);
    }

    public function test_cleanup_is_previewable_idempotent_and_preserves_records_and_interactions(): void
    {
        $first=$this->article('Een zonnige dag');
        $second=$this->article('EEN zonnige dag!', ['source_name'=>'Andere bron','views_count'=>9,'likes_count'=>4]);
        $service=app(NewsDuplicates::class);
        $this->assertCount(1,$service->clean()); $this->assertNull($second->fresh()->hidden_at);
        $this->artisan('news:duplicates',['--apply'=>true])->assertExitCode(0);
        $this->assertNotNull($second->fresh()->hidden_at); $this->assertNull($first->fresh()->hidden_at);
        $this->assertSame(4,$second->fresh()->likes_count); $this->assertSame(9,$second->fresh()->views_count);
        $this->assertSame(2,Article::count()); $this->assertSame(1,Article::visible()->count());
        $this->assertSame([],$service->clean(true)); Http::assertNothingSent();
    }

    public function test_both_translated_titles_are_checked_and_community_stories_are_kept(): void
    {
        $first=$this->article('Source headline',['nl'=>['title'=>'Blije bijen'],'en'=>['title'=>'Happy bees']]);
        $duplicate=$this->article('Other source headline',['nl'=>['title'=>'Andere kop'],'en'=>['title'=>'Happy bees!']]);
        $community=$this->article('Source headline',['origin'=>'community']);
        $rows=app(NewsDuplicates::class)->clean(true);
        $this->assertSame([['keep'=>$first->id,'hide'=>$duplicate->id,'title'=>'Andere kop']],$rows);
        $this->assertNull($community->fresh()->hidden_at);
    }

    public function test_queued_copy_is_not_published_and_does_not_need_an_ai_request(): void
    {
        $original=$this->article('Een mooie prestatie');
        $copy=$this->article('Een mooie prestatie!',['status'=>'pending']);
        app(NewsImporter::class)->assess($copy);
        $this->assertNotNull($copy->fresh()->hidden_at); Http::assertNothingSent();
        $copy->forceFill(['hidden_at'=>null,'status'=>'published'])->save();
        $this->assertNotNull($copy->fresh()->hidden_at);
        $this->assertNull($original->fresh()->hidden_at);
    }

    public function test_hidden_or_rejected_news_does_not_block_a_new_copy(): void
    {
        $this->article('Oud bericht',['hidden_at'=>now()]);
        $this->article('Afgewezen bericht',['status'=>'rejected']);
        $service=app(NewsDuplicates::class);
        $this->assertNull($service->find(new Article(['source_title'=>'Oud bericht'])));
        $this->assertNull($service->find(new Article(['source_title'=>'Afgewezen bericht'])));
        $this->assertSame('2026 10 scholen',NewsDuplicates::titleKey('2026: 10 scholen!'));
    }

    public function test_different_source_titles_are_checked_again_after_translation(): void
    {
        config(['news.gemini_key'=>'test-key','news.gemini_model'=>'test-model']);
        $this->article('Original source',['nl'=>['title'=>'Nieuwe kansen voor bijen'],'en'=>['title'=>'New chances for bees']]);
        $copy=$this->article('Other headline',['status'=>'pending']);
        $nl=['title'=>'Nieuwe kansen voor bijen!','teaser'=>'Goed nieuws','body'=>['Een positieve ontwikkeling.']];
        $en=['title'=>'More hope for bees','teaser'=>'Good news','body'=>['A positive development.']];
        Http::fake(['generativelanguage.googleapis.com/*'=>Http::response(['candidates'=>[
            ['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode([
                'decision'=>'suitable','reason'=>'Positief','category'=>'nature','nl'=>$nl,'en'=>$en,
            ])]]]],
        ]])]);
        app(NewsImporter::class)->assess($copy);
        $this->assertNotNull($copy->fresh()->hidden_at);
        $this->assertSame(1,Article::visible()->count()); Http::assertSentCount(1);
    }

    public function test_daily_card_shows_every_paragraph_in_the_selected_language_and_escapes_html(): void
    {
        $story=['id'=>1,'cat'=>'nature','story_urls'=>['nl'=>'/nl/story','en'=>'/en/story'],
            'photo'=>['url'=>'/test.png','generated'=>false],
            'nl'=>['title'=>'NL titel','teaser'=>'TEASER NIET NODIG','body'=>['NL eerste','NL laatste <script>alert(1)</script>']],
            'en'=>['title'=>'EN title','teaser'=>'SHORT ONLY','body'=>['EN first','EN final']]];
        $render=fn($locale)=>view('homepage.highlights',['highlights'=>[['kind'=>'daily','story'=>$story]],
            'locale'=>$locale,'category'=>'all','feed'=>['pagination'=>['current_page'=>1]]])->render();
        $nl=$render('nl'); $en=$render('en');
        $this->assertStringContainsString('<p>NL eerste</p>',$nl);
        $this->assertStringContainsString('NL laatste &lt;script&gt;',$nl);
        $this->assertStringNotContainsString('<script>',$nl);
        $this->assertStringNotContainsString('TEASER NIET NODIG',$nl);
        $this->assertStringContainsString('<p>EN final</p>',$en);
        $this->assertStringNotContainsString('NL eerste',$en);
    }
}
