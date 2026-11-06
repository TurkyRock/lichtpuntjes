<?php
namespace Tests\Feature;
use App\Models\{Article,LightspotSubmission,User};
use App\Jobs\ProcessLightspot;
use App\Services\{GeminiJson,ArticlePhotoFinder,LightspotProcessor,NewsServiceException};
use Illuminate\Support\Facades\{DB,Queue};
use Illuminate\Support\Str;
use Tests\TestCase;
class UserLightspotTest extends TestCase {
 protected function setUp():void {parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);DB::purge('sqlite');$this->artisan('migrate',['--force'=>true])->assertExitCode(0);Queue::fake();}
 private function user(array $changes=[]):User {return User::forceCreate(array_merge(['name'=>'Vriendelijke maker','email'=>Str::uuid().'@example.com','password'=>bcrypt('SunnyPassword123'),'email_verified_at'=>now(),'community_accepted_at'=>now(),'is_admin'=>false,'is_suspended'=>false],$changes));}
 private function fields():array {return ['title'=>'Mijn buur bracht een glimlach','body'=>str_repeat('Mijn buur hielp mij met de tuin. Samen genoten we van de bloemen en een kop thee. ',3),'category'=>'people','language'=>'nl','intentions'=>1];}
 private function submission(array $changes=[]):LightspotSubmission {return LightspotSubmission::create(array_merge(array_diff_key($this->fields(),['intentions'=>1]),['user_id'=>$this->user()->id,'status'=>'pending'],$changes));}
 private function aiResponse(string $decision='approve',float $confidence=.98):array {return ['decision'=>$decision,'confidence'=>$confidence,'reason_nl'=>'Een vriendelijk eigen verhaal.','reason_en'=>'A friendly original story.','nl'=>['title'=>'Gewijzigde titel','teaser'=>'Een behulpzame buur maakt de dag mooier.','body'=>['Een aangepaste AI-tekst.']],'en'=>['title'=>'My neighbour brought a smile','teaser'=>'A helpful neighbour brightens the day.','body'=>['My neighbour helped in the garden. We enjoyed the flowers together.']]];}
 private function ai(array $result):void {$this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->once()->andReturn($result));}
 public function test_guest_and_unverified_users_cannot_submit():void {$this->post('/lichtpuntje/schrijven',$this->fields())->assertRedirect('/inloggen');$this->actingAs($this->user(['email_verified_at'=>null]))->postJson('/lichtpuntje/schrijven',$this->fields())->assertForbidden();$this->assertDatabaseCount('lightspot_submissions',0);}
 public function test_submission_requires_rules_and_stays_private_in_queue():void {
  $u=$this->user();$this->actingAs($u)->postJson('/lichtpuntje/schrijven',array_diff_key($this->fields(),['intentions'=>1]))->assertUnprocessable();
  $this->post('/lichtpuntje/schrijven',$this->fields()+['status'=>'published','user_id'=>999,'is_admin'=>true])->assertRedirect('/mijn-lichtpuntjes');$s=LightspotSubmission::first();$this->assertSame($u->id,$s->user_id);$this->assertSame('pending',$s->status);$this->assertDatabaseCount('articles',0);Queue::assertPushed(ProcessLightspot::class,fn($job)=>$job->submissionId===$s->id&&$job->queue==='lichtpuntjes'&&$job->connection==='database');
 }
 public function test_ai_approval_preserves_original_and_publishes_after_image():void {
  $s=$this->submission();$this->ai($this->aiResponse());$this->mock(ArticlePhotoFinder::class,function($m){$m->shouldReceive('attach')->twice()->withArgs(function($a){$this->assertContains($a->status,['community_pending','published']);return true;})->andReturn(true);$m->shouldReceive('current')->andReturn(true);});
  app(LightspotProcessor::class)->process($s);$s->refresh();$this->assertSame('published',$s->status);$this->assertSame($s->title,$s->article->nl['title']);$this->assertSame(trim($s->body),implode("\n\n",$s->article->nl['body']));$this->assertSame('community',$s->article->origin);$this->getJson('/news/feed')->assertJsonPath('pagination.total',1)->assertJsonPath('articles.0.origin','community');
  app(LightspotProcessor::class)->process($s);$this->assertDatabaseCount('articles',1);$this->assertSame(0,app(\App\Services\Lightpoints::class)->summary($s->user)['points']);
  $s->update(['status'=>'image_wait']);app(LightspotProcessor::class)->process($s);$this->assertSame(0,app(\App\Services\Lightpoints::class)->summary($s->user)['points']);
 }
 public function test_rejection_or_uncertainty_never_requests_image():void {
  foreach([['reject',.98,'rejected'],['approve',.60,'doubt']] as [$d,$c,$expected]){
   $s=$this->submission();$this->ai($this->aiResponse($d,$c));$this->mock(ArticlePhotoFinder::class,fn($m)=>$m->shouldNotReceive('attach'));app(LightspotProcessor::class)->process($s);$this->assertSame($expected,$s->fresh()->status);
  }$this->assertDatabaseCount('articles',0);$this->assertDatabaseCount('lightpoint_events',0);
 }
 public function test_ai_failure_goes_to_human_review():void {
  $s=$this->submission();$this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->andThrow(new \RuntimeException('private-key raw error')));app(LightspotProcessor::class)->process($s);$this->assertSame('doubt',$s->fresh()->status);$this->assertTrue($s->fresh()->ai_error);$this->assertStringNotContainsString('private-key',json_encode($s->fresh()->reason));
 }
 public function test_image_outage_keeps_article_private_and_can_resume():void {
  $s=$this->submission();$this->ai($this->aiResponse());$this->mock(ArticlePhotoFinder::class,fn($m)=>$m->shouldReceive('attach')->once()->andThrow(new NewsServiceException('Quota')));app(LightspotProcessor::class)->process($s);$this->assertSame('image_wait',$s->fresh()->status);$this->assertSame(0,app(\App\Services\Lightpoints::class)->summary($s->user)['points']);$this->assertSame('community_pending',$s->fresh()->article->status);$this->getJson('/news/feed')->assertJsonPath('pagination.total',0);
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldNotReceive('ask'));$this->mock(ArticlePhotoFinder::class,function($m){$m->shouldReceive('attach')->once()->andReturn(true);$m->shouldReceive('current')->andReturn(true);});app(LightspotProcessor::class)->process($s);$this->assertSame('published',$s->fresh()->status);$this->assertDatabaseCount('articles',1);
 }
 public function test_only_author_can_see_edit_or_appeal_rejected_story():void {
  $s=$this->submission(['status'=>'rejected','reason'=>['nl'=>'Alleen voor schrijver.','en'=>'Private feedback.']]);$this->actingAs($this->user())->get('/mijn-lichtpuntjes/'.$s->id.'/aanpassen')->assertForbidden();$this->post('/mijn-lichtpuntjes/'.$s->id.'/herbeoordeling')->assertForbidden();$this->get('/mijn-lichtpuntjes')->assertDontSee($s->title);
  $this->actingAs($s->user)->get('/mijn-lichtpuntjes/'.$s->id.'/aanpassen')->assertOk()->assertSee('Alleen voor schrijver.');$this->post('/mijn-lichtpuntjes/'.$s->id.'/herbeoordeling')->assertRedirect();$this->assertSame('doubt',$s->fresh()->status);
 }
 public function test_revision_returns_to_review_and_logs_previous_story():void {
  $s=$this->submission(['status'=>'rejected']);$this->actingAs($s->user)->post('/mijn-lichtpuntjes/'.$s->id.'/aanpassen',array_replace($this->fields(),['body'=>str_repeat('Een andere mooie ervaring met een buur en de tuin. ',4)]))->assertRedirect('/mijn-lichtpuntjes');$this->assertSame('pending',$s->fresh()->status);$this->assertSame(2,$s->fresh()->revision);$this->assertDatabaseHas('lightspot_reviews',['submission_id'=>$s->id,'decision'=>'revision']);Queue::assertPushed(ProcessLightspot::class);
 }
 public function test_only_owner_can_moderate_and_rejection_hides_published_story():void {
  $s=$this->submission(['status'=>'published']);$a=Article::create(['fingerprint'=>hash('sha256','personal'),'origin'=>'community','author_id'=>$s->user_id,'source_title'=>$s->title,'source_name'=>$s->user->name,'source_url'=>'https://example.com','status'=>'published','category'=>'people','nl'=>$this->aiResponse()['nl'],'en'=>$this->aiResponse()['en']]);$s->update(['article_id'=>$a->id]);
  $this->actingAs($this->user())->get('/beheer/lichtpuntjes')->assertForbidden();$this->post('/beheer/lichtpuntjes/'.$s->id,['decision'=>'rejected','note'=>'Past niet.'])->assertForbidden();
  $this->actingAs($this->user(['is_admin'=>true]))->get('/beheer/lichtpuntjes?status=published')->assertOk()->assertSee($s->title);$this->post('/beheer/lichtpuntjes/'.$s->id,['decision'=>'rejected','note'=>'Dit past niet bij onze regels.'])->assertRedirect();$this->getJson('/news/'.$a->id.'/story')->assertNotFound();
 }
 public function test_suspended_author_stories_are_hidden():void {
  $u=$this->user(['is_suspended'=>true]);$a=Article::create(['fingerprint'=>hash('sha256','hidden'),'origin'=>'community','author_id'=>$u->id,'source_title'=>'Hidden','source_name'=>'Author','source_url'=>'https://example.com','status'=>'published']);$this->getJson('/news/feed')->assertJsonPath('pagination.total',0);$this->getJson('/news/'.$a->id.'/story')->assertNotFound();
 }
 public function test_daily_limits_and_contact_details_are_enforced():void {
  $u=$this->user();$this->actingAs($u)->postJson('/lichtpuntje/schrijven',array_replace($this->fields(),['body'=>$this->fields()['body'].' Mail me via x@example.com']))->assertUnprocessable();for($i=0;$i<3;$i++)$this->submission(['user_id'=>$u->id]);$this->post('/lichtpuntje/schrijven',$this->fields())->assertStatus(429);
 }
 public function test_form_and_empty_list_render_in_both_languages():void {
  $this->actingAs($this->user());foreach(['nl','en'] as $lang){$this->get('/lichtpuntje/schrijven?lang='.$lang)->assertRedirect('/mijn-lichtpuntjes?lang='.$lang.'#new-story');$this->get('/mijn-lichtpuntjes?lang='.$lang)->assertOk();}$this->get('/')->assertOk()->assertSee('Deel een lichtpuntje');
 }
 public function test_reports_are_private_unique_and_visible_only_to_admin():void {
  $s=$this->submission(['status'=>'published']);$a=Article::create(['fingerprint'=>hash('sha256','reported'),'origin'=>'community','author_id'=>$s->user_id,'source_title'=>$s->title,'source_name'=>'Author','source_url'=>'https://example.com','status'=>'published']);$s->update(['article_id'=>$a->id]);
  $reporter=$this->user();$this->actingAs($reporter)->postJson('/news/'.$a->id.'/melden',['note'=>'Dit bevat privégegevens.'])->assertOk();$this->postJson('/news/'.$a->id.'/melden',['note'=>'Dit bevat privégegevens.'])->assertOk();$this->assertDatabaseCount('lightspot_reports',1);$this->getJson('/news/'.$a->id.'/story')->assertDontSee('privégegevens');
  $this->actingAs($this->user(['is_admin'=>true]))->get('/beheer/lichtpuntjes?status=reported')->assertOk()->assertSee('Dit bevat privégegevens.');
 }
 public function test_background_job_reschedules_image_outage():void {
  $s=$this->submission(['status'=>'image_wait']);$this->mock(LightspotProcessor::class,fn($m)=>$m->shouldReceive('process')->once());
  $queued=\Mockery::mock(\Illuminate\Contracts\Queue\Job::class);$queued->shouldReceive('release')->once()->with(3600);$job=new ProcessLightspot($s->id);$job->setJob($queued);$job->handle(app(LightspotProcessor::class));
 }
 public function test_human_approval_requires_translation_and_image_before_publication():void {
  $s=$this->submission(['status'=>'doubt']);$admin=$this->user(['is_admin'=>true]);app(LightspotProcessor::class)->decide($s,$admin,'image_wait','Een vriendelijk verhaal.');$this->assertDatabaseCount('articles',0);
  $this->ai($this->aiResponse());$this->mock(ArticlePhotoFinder::class,function($m){$m->shouldReceive('attach')->once()->andReturn(true);$m->shouldReceive('current')->andReturn(true);});app(LightspotProcessor::class)->process($s);$this->assertSame('published',$s->fresh()->status);$this->assertSame($admin->id,$s->fresh()->moderated_by);
 }
 public function test_topic_is_removed_and_category_is_set_by_server():void {
  $this->actingAs($this->user())->get('/mijn-lichtpuntjes')->assertOk()->assertDontSee('name="category"',false);
  $fields=$this->fields();unset($fields['category']);
  $this->post('/lichtpuntje/schrijven',$fields)->assertRedirect('/mijn-lichtpuntjes');
  $this->assertSame('community',LightspotSubmission::first()->category);
  $fields['body'].=' Wat een fijne dag was dat.';$fields['category']='sport';
  $this->post('/lichtpuntje/schrijven',$fields)->assertRedirect('/mijn-lichtpuntjes');
  $this->assertSame('community',LightspotSubmission::latest('id')->first()->category);
 }
}
