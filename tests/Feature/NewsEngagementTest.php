<?php
namespace Tests\Feature;
use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewsEngagementTest extends TestCase {
    private string $visitor;
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        $this->artisan('migrate',['--force'=>true])->assertExitCode(0);
        $this->visitor=(string)Str::uuid();
        $this->withCredentials()->withCookie('lichtpunt_consent','v2|yes|'.now()->timestamp)->withCookie('lichtpunt_visitor',$this->visitor);
    }
    private function story(array $extra=[]): Article {
        $id=(string)Str::uuid();
        return Article::create(array_merge(['fingerprint'=>hash('sha256',$id),'source_title'=>'Good news',
            'source_description'=>'Description','source_name'=>'Test source','source_url'=>'https://example.org/'.$id,
            'status'=>'published','category'=>'media','published_at'=>'2026-10-10 07:00:00',
            'nl'=>['title'=>'Goed nieuws','teaser'=>'Een lichtpuntje','body'=>['Een mooi verhaal.']],
            'en'=>['title'=>'Good news','teaser'=>'A bright spot','body'=>['A happy story.']]],$extra));
    }
    public function test_repeated_opening_counts_once_per_browser_per_day(): void {
        $a=$this->story();
        $this->postJson('/news/'.$a->id.'/read')->assertOk()->assertJsonPath('views_count',1);
        $this->postJson('/news/'.$a->id.'/read')->assertOk()->assertJsonPath('views_count',1);
        $this->withCookie('lichtpunt_visitor',(string)Str::uuid());
        $this->postJson('/news/'.$a->id.'/read')->assertOk()->assertJsonPath('views_count',2);
        $this->travel(1)->days();
        $this->postJson('/news/'.$a->id.'/read')->assertOk()->assertJsonPath('views_count',3);
        $this->travelBack();
    }
    private function account():User {
        $u=User::create(['name'=>'Zonnetje','email'=>Str::uuid().'@example.com','password'=>'SunnyPassword123']);$u->forceFill(['email_verified_at'=>now(),'community_accepted_at'=>now()])->save();return $u;
    }
    public function test_like_and_unlike_are_idempotent_and_personal(): void {
        $a=$this->story();$first=$this->account();$second=$this->account();$this->actingAs($first);
        $this->postJson('/news/'.$a->id.'/like',['liked'=>true])->assertOk()->assertJsonPath('likes_count',1);
        $this->postJson('/news/'.$a->id.'/like',['liked'=>true])->assertOk()->assertJsonPath('likes_count',1);
        $this->getJson('/news/feed')->assertJsonPath('articles.0.liked',true);
        $this->actingAs($second)->getJson('/news/feed')->assertJsonPath('articles.0.liked',false);
        $this->postJson('/news/'.$a->id.'/like',['liked'=>false])->assertJsonPath('likes_count',1);
        $this->actingAs($first)->postJson('/news/'.$a->id.'/like',['liked'=>false])->assertJsonPath('likes_count',0);
        $this->postJson('/news/'.$a->id.'/like',['liked'=>false])->assertJsonPath('likes_count',0);
    }
    public function test_three_rankings_and_filters(): void {
        $newest=$this->story(['published_at'=>'2026-10-10 08:00:00']);
        $read=$this->story(['views_count'=>20,'likes_count'=>1]);
        $liked=$this->story(['category'=>'sport','views_count'=>1,'likes_count'=>10]);
        $this->getJson('/news/feed?sort=newest')->assertOk()->assertJsonPath('articles.0.id',(string)$newest->id);
        $this->getJson('/news/feed?sort=reads')->assertOk()->assertJsonPath('articles.0.id',(string)$read->id);
        $this->getJson('/news/feed?sort=likes')->assertOk()->assertJsonPath('articles.0.id',(string)$liked->id);
        $this->getJson('/news/feed?sort=likes&category=media')->assertOk()->assertJsonPath('articles.0.id',(string)$read->id);
    }
    public function test_unpublished_articles_cannot_receive_reads_or_likes(): void {
        $a=$this->story(['status'=>'draft']);$this->actingAs($this->account());
        $this->postJson('/news/'.$a->id.'/read')->assertNotFound();
        $this->postJson('/news/'.$a->id.'/like',['liked'=>true])->assertNotFound();
        $this->assertSame(0,$a->fresh()->likes_count);
        $this->assertSame(0,$a->fresh()->views_count);
    }
    public function test_all_feed_balances_categories_across_pages_and_keeps_every_story(): void {
        $author=$this->account();
        foreach (['media','sport','energy','nature','science','people','community'] as $category) {
            for ($i=0;$i<($category==='sport'?30:3);$i++) {
                $this->story(['category'=>$category==='community'?'sport':$category,
                    'origin'=>$category==='community'?'community':'news',
                    'author_id'=>$category==='community'?$author->id:null,
                    'published_at'=>now()->subMinutes($i),'views_count'=>100-$i,'likes_count'=>50-$i]);
            }
        }
        foreach (['newest','reads','likes'] as $sort) {
            $first=$this->getJson('/news/feed?sort='.$sort)->assertOk()->assertJsonPath('pagination.total',48)->json('articles');
            $this->assertCount(7,array_unique(array_column(array_slice($first,0,7),'cat')));
            $this->assertLessThanOrEqual(2,count(array_filter($first,fn($a)=>$a['cat']==='sport')));
            $all=[];
            for($page=1;$page<=4;$page++) {
                $all=array_merge($all,$this->getJson('/news/feed?sort='.$sort.'&page='.$page)->assertOk()->json('articles'));
            }
            $this->assertCount(48,array_unique(array_column($all,'id')));
            foreach (array_unique(array_column($all,'cat')) as $cat) {
                $rows=array_values(array_filter($all,fn($a)=>$a['cat']===$cat));
                $key=$sort==='likes'?'likes_count':'views_count';
                $values=array_column($rows,$key);$sorted=$values;rsort($sorted);$this->assertSame($sorted,$values);
            }
        }
        $this->getJson('/news/feed?category=sport')->assertOk()->assertJsonPath('pagination.total',30);
        $this->getJson('/news/feed?category=community')->assertOk()->assertJsonPath('pagination.total',3)->assertJsonPath('articles.0.cat','community');
        $this->getJson('/news/feed?page=999')->assertOk()->assertJsonPath('pagination.current_page',4);
    }
    public function test_empty_balanced_feed_is_valid(): void {
        $this->getJson('/news/feed')->assertOk()->assertJsonPath('pagination.total',0)->assertJsonCount(0,'articles');
    }
}
