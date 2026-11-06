<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,Article,LightspotSubmission};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http,Queue,File};
use Illuminate\Support\Str;
class StoryStudioTest extends TestCase
{
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();Queue::fake();}
 private function member(array $extra=[]):User {return User::forceCreate($extra+['name'=>'Schrijver','email'=>Str::uuid().'@example.test','password'=>'TestPassword12345','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 private function story(User $u,string $status='pending',string $title='Een klein gebaar bracht een glimlach'):LightspotSubmission{return LightspotSubmission::create(['user_id'=>$u->id,'status'=>$status,'title'=>$title,'body'=>str_repeat('De buurvrouw hielp met onze tuin. Samen genoten we van de bloemen en een kop thee. ',3),'language'=>'nl','category'=>'community']);}
 public function test_writer_and_ai_explanation_render_in_both_languages_without_old_intro():void {
  $this->actingAs($this->member());foreach(['nl','en'] as $lang){$r=$this->get('/mijn-lichtpuntjes?lang='.$lang)->assertOk()->assertSee('id="story-body"',false)->assertSee('minlength="120"',false)->assertSee('maxlength="6000"',false)->assertSee($lang==='nl'?'Na goedkeuring maakt AI een passende afbeelding':'After approval, AI creates a fitting image')->assertDontSee('Jouw lichtpuntjes, hun status en feedback.')->assertDontSee('My stories &amp; feedback',false);}
 }
 public function test_collection_contains_only_own_stories_and_keeps_review_actions():void {
  $u=$this->member();$s=$this->story($u,'rejected');$s->update(['reason'=>['nl'=>'Vertel vooral wat jou hoop gaf.','en'=>'Focus on what gave you hope.']]);$this->story($this->member(),'pending','Verhaal van iemand anders');
  $this->actingAs($u)->get('/mijn-lichtpuntjes')->assertOk()->assertSee($s->title)->assertDontSee('Verhaal van iemand anders')->assertSee('Verhaal aanpassen')->assertSee('Vraag een menselijke blik')->assertSee('Vertel vooral wat jou hoop gaf.');
  $this->get('/mijn-lichtpuntjes/'.$s->id.'/aanpassen')->assertOk()->assertSee('id="story-body"',false)->assertSee($s->title);
 }
 public function test_unverified_writer_sees_gate_instead_of_form():void {
  $this->actingAs($this->member(['email_verified_at'=>null]))->get('/mijn-lichtpuntjes')->assertOk()->assertDontSee('id="story-body"',false)->assertSee('Bevestig je e-mailadres');
 }
 public function test_pagination_preserves_language_and_old_input_is_escaped():void {
  $u=$this->member();for($i=0;$i<7;$i++)$this->story($u);$this->actingAs($u)->get('/mijn-lichtpuntjes?lang=en')->assertOk()->assertSee('page=2')->assertSee('lang=en');
  $this->withSession(['_old_input'=>['title'=>'<script>alert(1)</script>','body'=>'<b>Test</b>','language'=>'en']])->get('/mijn-lichtpuntjes?lang=en')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
 }
 public function test_capture_design_when_requested():void {
  if(!getenv('STORY_CAPTURE')){$this->assertTrue(true);return;}
  $u=$this->member();$s=$this->story($u,'published','De buurttuin bloeit weer');
  $a=Article::create(['fingerprint'=>hash('sha256','studio-fixture'),'origin'=>'community','author_id'=>$u->id,'source_title'=>$s->title,'source_name'=>'Author','source_url'=>'https://example.test/story','status'=>'published','photo'=>['url'=>'/news-images/'.str_repeat('a',64).'.png']]);$s->update(['article_id'=>$a->id]);
  $this->story($u,'image_wait','Een onverwacht bedankje');$this->actingAs($u);$dir=base_path('../verification/story-render');File::ensureDirectoryExists($dir);
  foreach(['nl','en'] as $lang){$r=$this->get('/mijn-lichtpuntjes?lang='.$lang)->assertOk();File::put($dir.'/stories-'.$lang.'.html',$r->getContent());}
 }
}
