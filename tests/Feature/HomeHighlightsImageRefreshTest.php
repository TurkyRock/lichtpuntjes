<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Article,User};
use App\Services\{ArticlePhotoFinder,DailySpotlight,LichtpuntIllustrationPrompt};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{File,Http,DB};
class HomeHighlightsImageRefreshTest extends TestCase {
 use RefreshDatabase;
 private array $files=[];
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();$this->travelTo(\Carbon\Carbon::parse('2026-10-23 12:00:00','Europe/Amsterdam'));}
 protected function tearDown():void {File::delete($this->files);$this->travelBack();parent::tearDown();}
 private function story(int $number,array $photo=[]):Article {
  $bytes=File::get(public_path('images/lichtpunt-zon.png')).'highlight-'.$number;$hash=hash('sha256',$bytes);$path=public_path('news-images/'.$hash.'.png');File::ensureDirectoryExists(dirname($path));File::put($path,$bytes);$this->files[]=$path;
  return Article::create(['fingerprint'=>bin2hex(random_bytes(16)),'origin'=>'news','status'=>'published','source_title'=>'Hoopvol nieuws '.$number,'source_url'=>'https://example.test/'.$number,'source_name'=>'Testbron','category'=>['nature','science','energy'][$number%3],'daily_score'=>100-$number,'daily_scored_at'=>now(),'published_at'=>now()->subHour(),'nl'=>['title'=>'Hoopvol nieuws '.$number,'teaser'=>'Een mooie ontwikkeling.','body'=>['Een positief verhaal met ruimte voor hoop.']],'en'=>['title'=>'Hopeful news '.$number,'teaser'=>'A positive development.','body'=>['A positive story with room for hope.']],'photo'=>$photo+['url'=>'/news-images/'.$hash.'.png','sha256'=>$hash,'generated'=>true,'style'=>'lichtpunt-torn-paper-v2','model'=>'previous-model','subject_hash'=>'old-recipe']]);
 }
 public function test_all_three_highlights_remain_visible_before_new_batch_is_generated():void {
  $daily=$this->story(1);$read=$this->story(2);$liked=$this->story(3);
  DB::table('article_reads')->insert(['article_id'=>$read->id,'visitor_hash'=>str_repeat('f',64),'read_date'=>'2026-10-23']);
  $user=User::forceCreate(['name'=>'Sunny','email'=>'sunny@example.test','password'=>'TestPassword123','email_verified_at'=>now(),'community_accepted_at'=>now()]);
  DB::table('user_article_likes')->insert(['article_id'=>$liked->id,'user_id'=>$user->id,'created_at'=>now()]);
  $finder=app(ArticlePhotoFinder::class);$this->assertFalse($finder->current($daily));$this->assertTrue($finder->available($daily));
  $cards=app(DailySpotlight::class)->highlights();$this->assertSame(['daily','reads','likes'],array_column($cards,'kind'));$this->assertSame([$daily->id,$read->id,$liked->id],array_map(fn($c)=>$c['article']->id,$cards));
  foreach(['/' => ['Lichtpuntje van de dag','Meest gelezen · 7 dagen','Meeste likes · 7 dagen'],'/en'=>['Bright spot of the day','Most read · 7 days','Most liked · 7 days']] as $url=>$labels){$r=$this->get($url)->assertOk()->assertSee('id="dailyHighlights"',false);foreach($labels as $label)$r->assertSee($label);foreach([$daily,$read,$liked] as $a)$r->assertSee('data-feature-card="'.$a->id.'"',false);}
  $this->assertFalse($finder->current($daily->fresh()));Http::assertNothingSent();
 }
 public function test_failed_refresh_and_changed_provider_do_not_remove_existing_daily_edition():void {
  $a=$this->story(1,['requested_style'=>LichtpuntIllustrationPrompt::COLLAGE_STYLE,'visual_brief'=>['content_hash'=>'old-context']]);$first=app(DailySpotlight::class)->choose();$this->assertSame($a->id,$first->article_id);
  config(['news_images.provider'=>'comfyui','news_images.checkpoint'=>'different-model.safetensors']);$a->update(['daily_score'=>null,'en'=>array_merge($a->en,['teaser'=>'Updated text.'])]);$this->assertSame($first->article_id,app(DailySpotlight::class)->choose()->article_id);$this->assertTrue(app(ArticlePhotoFinder::class)->available($a->fresh()));Http::assertNothingSent();
 }
 public function test_missing_corrupt_and_unsafe_images_still_do_not_qualify():void {
  $a=$this->story(1);$path=public_path(ltrim($a->photo['url'],'/'));File::put($path,'corrupt');$this->assertFalse(app(ArticlePhotoFinder::class)->available($a));$this->assertNull(app(DailySpotlight::class)->choose());File::delete($path);$this->assertFalse(app(ArticlePhotoFinder::class)->available($a));$a->photo=['generated'=>true,'url'=>'https://untrusted.test/image.png','sha256'=>str_repeat('a',64)];$this->assertFalse(app(ArticlePhotoFinder::class)->available($a));Http::assertNothingSent();
 }
 public function test_hidden_articles_still_do_not_qualify_despite_available_image():void {
  $a=$this->story(1);$this->assertNotNull(app(DailySpotlight::class)->choose());$a->update(['hidden_at'=>now()]);$this->assertNull(app(DailySpotlight::class)->choose());$this->assertSame([],app(DailySpotlight::class)->highlights());Http::assertNothingSent();
 }
}
