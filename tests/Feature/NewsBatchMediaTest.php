<?php
namespace Tests\Feature;
use App\Models\Article;
use App\Services\NewsImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;
class NewsBatchMediaTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:',
            'cache.default'=>'array','news.gemini_key'=>'test-only','news.gemini_model'=>'test-model',
            'news.newsdata_key'=>'test-only']);
        DB::purge('sqlite');
        $this->artisan('migrate',['--force'=>true])->assertExitCode(0);
        Http::preventStrayRequests();
    }
    private function article(): Article {
        $id=(string)Str::uuid();
        return Article::create(['fingerprint'=>hash('sha256',$id),'source_title'=>'Singer raises money for charity',
            'source_description'=>str_repeat('A singer raised money at a charity concert to support a community programme. ',3),
            'source_name'=>'Test','source_url'=>'https://example.org/'.$id,'status'=>'pending']);
    }
    public function test_media_is_accepted_and_published_in_both_languages(): void {
        $a=$this->article();
        $nl=['title'=>'Zanger helpt goed doel','teaser'=>'Het concert bracht geld op.','body'=>['Een zanger zamelde geld in tijdens een benefietconcert.']];
        $en=['title'=>'Singer helps charity','teaser'=>'The concert raised money.','body'=>['A singer raised money at a charity concert.']];
        Http::fake(['https://generativelanguage.googleapis.com/*'=>Http::response(['candidates'=>[
            ['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode(['decision'=>'suitable','reason'=>'Positieve actie','category'=>'media','nl'=>$nl,'en'=>$en])]]]]]])]);
        app(NewsImporter::class)->assess($a);
        $this->assertSame('media',$a->fresh()->category);
        $this->assertSame('published',$a->fresh()->status);
        $this->assertSame($nl,$a->fresh()->nl);
        $this->assertSame($en,$a->fresh()->en);
    }
    public function test_batch_limit_processes_only_requested_items_without_photo_calls(): void {
        for($i=0;$i<3;$i++) $this->article();
        $mock=Mockery::mock(NewsImporter::class);
        $mock->shouldReceive('assess')->twice()->andReturnUsing(fn($a)=>$a->update(['status'=>'rejected']));
        $this->app->instance(NewsImporter::class,$mock);
        $this->artisan('news:import',['--process-only'=>true,'--limit'=>2,'--skip-photos'=>true])->assertExitCode(0);
        $this->assertSame(2,Article::where('status','rejected')->count());
        $this->assertSame(1,Article::where('status','pending')->count());
        Http::assertNothingSent();
    }
    public function test_pagination_continues_beyond_old_five_page_limit(): void {
        $sequence=Http::sequence();
        for($i=0;$i<6;$i++) {
            $payload=['status'=>'success','results'=>[['title'=>'Test '.$i,'description'=>'Source text','link'=>'https://example.org/page-'.$i]]];
            if($i<5) $payload['nextPage']='cursor-'.($i+1);
            $sequence->push($payload);
        }
        Http::fake(['https://newsdata.io/*'=>$sequence]);
        $this->assertSame(6,app(NewsImporter::class)->fetch(6));
        Http::assertSentCount(6);
        Http::assertSent(fn($r)=>str_contains($r['category'],'entertainment')&&($r['page']??null)==='cursor-5');
    }
}
