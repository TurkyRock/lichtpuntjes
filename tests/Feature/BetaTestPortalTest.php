<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{BetaAnswer,BetaParticipant,User};
use App\Services\BetaTests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
class BetaTestPortalTest extends TestCase {
 use RefreshDatabase;
 private ?string $activeParticipant=null;
 protected function setUp():void {parent::setUp();config(['beta.enabled'=>true,'beta.closed_site'=>true,'session.secure'=>false]);Http::preventStrayRequests();}
 private function person(string $name='Tester'):array{return app(BetaTests::class)->invite($name);}
 private function admin():User{return User::forceCreate(['name'=>'Erik','email'=>'erik@example.test','password'=>'TestPassword123','is_admin'=>true,'email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 private function asPerson(BetaParticipant $p):static{$this->activeParticipant=$p->id;return $this->withSession(['beta.participant'=>$p->id,'beta.version'=>$p->access_version]);}
 private function answer(array $extra=[]):array{return $extra+['participant_id'=>$this->activeParticipant,'outcome'=>'yes','ease'=>4,'device'=>'desktop','comment'=>'Dit ging prettig.','page_path'=>'/momentjes','version'=>0];}
 public function test_disabled_beta_preserves_public_site_and_hides_test_pages():void{config(['beta.enabled'=>false]);$this->get('/')->assertOk();$this->get('/testgroep')->assertNotFound();$this->get('/testgroep/toegang')->assertNotFound();}
 public function test_closed_site_requires_test_access_and_blocks_indexing():void{$this->get('/')->assertRedirect('/testgroep/toegang')->assertHeader('X-Robots-Tag','noindex, nofollow');$this->get('/robots.txt')->assertSee('Disallow: /');$this->getJson('/news/feed')->assertForbidden();}
 public function test_hashed_code_grants_cookie_and_session_without_echoing_secret():void{[$p,$code]=$this->person();$this->assertNotSame($code,$p->token_hash);$this->post('/testgroep/toegang',['code'=>$code])->assertRedirect('/testgroep')->assertCookie('beta_access')->assertSessionHas('beta.participant',$p->id);$this->get('/testgroep')->assertOk()->assertDontSee($code)->assertSee('Jouw rondje zonneschijn');$this->get('/')->assertOk();}
 public function test_invalid_expired_and_revoked_codes_cannot_enter():void{[$p,$code]=$this->person();$this->post('/testgroep/toegang',['code'=>str_repeat('a',48)])->assertSessionHasErrors();$p->update(['expires_at'=>now()->subMinute()]);$this->post('/testgroep/toegang',['code'=>$code])->assertSessionHasErrors();$p->update(['expires_at'=>now()->addDay(),'revoked_at'=>now()]);$this->post('/testgroep/toegang',['code'=>$code])->assertSessionHasErrors();}
 public function test_only_three_admin_created_invites():void{$this->post('/beheer/testgroep/uitnodigen',['label'=>'one'])->assertRedirect('/inloggen');$this->actingAs($this->admin())->withSession(['auth.password_confirmed_at'=>now()->timestamp]);foreach([1,2,3] as $n)$this->post('/beheer/testgroep/uitnodigen',['label'=>'Tester '.$n])->assertRedirect()->assertSessionHas('beta_invite');$this->post('/beheer/testgroep/uitnodigen',['label'=>'Fourth'])->assertUnprocessable();$this->assertSame(3,BetaParticipant::count());$this->get('/beheer/testgroep')->assertOk()->assertSee('3/3 testpersonen');}
 public function test_autosave_is_versioned_and_does_not_overwrite_other_tab():void{[$p]=$this->person();$this->asPerson($p)->putJson('/testgroep/antwoord/first',$this->answer())->assertOk()->assertJsonPath('version',1);$this->putJson('/testgroep/antwoord/first',$this->answer(['comment'=>'Stale']))->assertConflict();$this->putJson('/testgroep/antwoord/first',$this->answer(['version'=>1,'comment'=>'Nieuwe toelichting']))->assertOk()->assertJsonPath('version',2);$this->get('/testgroep')->assertSee('Nieuwe toelichting');}
 public function test_answers_are_isolated_between_testers():void{[$a]=$this->person('First');[$b]=$this->person('Second');$this->asPerson($a)->putJson('/testgroep/antwoord/first',$this->answer(['comment'=>'FIRST_PRIVATE_NOTE']))->assertOk();$this->asPerson($b)->get('/testgroep')->assertDontSee('FIRST_PRIVATE_NOTE');$this->putJson('/testgroep/antwoord/first',$this->answer(['participant_id'=>$a->id,'comment'=>'WRONG_PERSON']))->assertForbidden();$this->putJson('/testgroep/antwoord/first',$this->answer(['comment'=>'SECOND_NOTE']))->assertOk();$this->assertDatabaseHas('beta_answers',['participant_id'=>$a->id,'comment'=>'FIRST_PRIVATE_NOTE']);$this->assertDatabaseHas('beta_answers',['participant_id'=>$b->id,'comment'=>'SECOND_NOTE']);}
 public function test_drafts_do_not_complete_round_and_finished_round_is_read_only():void{[$p]=$this->person();$this->asPerson($p)->putJson('/testgroep/antwoord/first',$this->answer(['outcome'=>'not_yet']))->assertOk();$this->postJson('/testgroep/afronden',['participant_id'=>$this->activeParticipant])->assertUnprocessable();$this->putJson('/testgroep/antwoord/first',$this->answer(['version'=>1]))->assertOk();foreach(array_diff(array_keys(BetaTests::TASKS),['first']) as $key)$this->putJson('/testgroep/antwoord/'.$key,$this->answer(['outcome'=>'skipped']))->assertOk();$this->postJson('/testgroep/afronden',['participant_id'=>$this->activeParticipant])->assertOk();$this->assertNotNull($p->fresh()->completed_at);$this->putJson('/testgroep/antwoord/first',$this->answer(['version'=>2]))->assertConflict();}
 public function test_cookie_access_survives_missing_login_session_and_revocation_is_immediate():void{[$p]=$this->person();$this->withCookie('beta_access',$p->id.'.'.$p->access_version)->get('/testgroep')->assertOk();$p->update(['revoked_at'=>now(),'access_version'=>2]);$this->get('/')->assertRedirect('/testgroep/toegang');$this->putJson('/testgroep/antwoord/first',$this->answer())->assertForbidden();}
 public function test_unknown_tasks_unsafe_paths_and_invalid_scores_are_rejected():void{[$p]=$this->person();$this->asPerson($p)->putJson('/testgroep/antwoord/unknown',$this->answer())->assertNotFound();foreach(['https://evil.test','//evil.test','/page?secret=123'] as $path)$this->putJson('/testgroep/antwoord/first',$this->answer(['page_path'=>$path]))->assertUnprocessable();$this->putJson('/testgroep/antwoord/first',$this->answer(['ease'=>99]))->assertUnprocessable();$this->assertSame(0,BetaAnswer::count());}
 public function test_admin_only_export_is_formula_safe_and_no_feedback_goes_to_ai():void{[$p]=$this->person();$this->asPerson($p)->putJson('/testgroep/antwoord/first',$this->answer(['comment'=>'=HYPERLINK("https://evil.test")']))->assertOk();$u=User::forceCreate(['name'=>'Reader','email'=>'reader@example.test','password'=>'TestPassword123','email_verified_at'=>now()]);$this->actingAs($u)->get('/beheer/testgroep')->assertForbidden();$this->actingAs($this->admin())->withSession(['auth.password_confirmed_at'=>now()->timestamp])->get('/beheer/testgroep')->assertOk();$r=$this->get('/beheer/testgroep/export')->assertOk()->assertDownload('testgroep-antwoorden.csv');$this->assertStringContainsString("'=HYPERLINK",$r->streamedContent());Http::assertNothingSent();}
 public function test_rendering_escapes_test_labels_and_comments():void{[$p]=$this->person('<script>bad()</script>');$this->asPerson($p)->putJson('/testgroep/antwoord/first',$this->answer(['comment'=>'<img src=x onerror=bad()>']))->assertOk();$this->get('/testgroep')->assertDontSee('<script>bad()</script>',false)->assertSee('&lt;script&gt;bad()&lt;/script&gt;',false);Http::assertNothingSent();}
 public function test_admin_can_rotate_lost_link_without_adding_a_fourth_tester():void{[$p,$old]=$this->person();$this->actingAs($this->admin())->withSession(['auth.password_confirmed_at'=>now()->timestamp])->post('/beheer/testgroep/'.$p->id.'/nieuwe-link')->assertRedirect()->assertSessionHas('beta_invite');$this->assertNotSame(hash('sha256',$old),$p->fresh()->token_hash);$this->assertSame(2,$p->fresh()->access_version);$this->assertSame(1,BetaParticipant::count());}
 public function test_capture_beta_design_when_requested():void{if(!getenv('BETA_CAPTURE')){$this->assertTrue(true);return;}[$p]=$this->person('Testpersoon 1');$html=$this->asPerson($p)->get('/testgroep')->assertOk()->getContent();\Illuminate\Support\Facades\File::ensureDirectoryExists(base_path('../beta-work/render'));\Illuminate\Support\Facades\File::put(base_path('../beta-work/render/portal.html'),$html);$this->actingAs($this->admin())->withSession(['auth.password_confirmed_at'=>now()->timestamp]);$html=$this->get('/beheer/testgroep')->assertOk()->getContent();\Illuminate\Support\Facades\File::put(base_path('../beta-work/render/admin.html'),$html);}

 public function test_session_refresh_is_private_and_returns_current_test_identity():void{[$p]=$this->person();$this->asPerson($p)->getJson('/testgroep/sessie')->assertOk()->assertJsonPath('participant_id',$p->id)->assertJsonStructure(['csrf_token']);$p->update(['revoked_at'=>now()]);$this->getJson('/testgroep/sessie')->assertForbidden();}

 public function test_standalone_portal_does_not_close_or_noindex_the_main_website():void{config(['beta.closed_site'=>false]);$r=$this->get('/')->assertOk();$this->assertNotSame('noindex, nofollow',$r->headers->get('X-Robots-Tag'));$this->get('/testgroep')->assertRedirect('/testgroep/toegang');$this->get('/testgroep/toegang')->assertOk()->assertHeader('X-Robots-Tag','noindex, nofollow');}

 private function reflection(array $extra=[]):array{return $extra+['good'=>'De vrolijke knipselstijl.','less_good'=>'De weg naar mijn profiel was onduidelijk.','return_choice'=>'yes','return_reason'=>'Om mijn dag positief af te sluiten.','improvement'=>'Een duidelijkere profielknop.'];}
 public function test_final_summary_keeps_drafts_and_requires_a_reason_before_marking_done():void{
  [$p]=$this->person();$this->asPerson($p);
  $this->putJson('/testgroep/antwoord/summary',$this->answer(['outcome'=>'not_yet','summary'=>['good'=>'Een eerste gedachte']]))->assertOk()->assertJsonPath('version',1);
  $this->get('/testgroep')->assertOk()->assertSee('Een eerste gedachte')->assertSee('Zou je terugkomen op de website?');
  $this->putJson('/testgroep/antwoord/summary',$this->answer(['version'=>1,'summary'=>$this->reflection(['return_reason'=>''])]))->assertUnprocessable()->assertJsonValidationErrors('summary.return_reason');
  $this->assertSame(1,$p->answers()->where('task','summary')->first()->version);
  foreach(['yes','maybe','no'] as $i=>$choice)$this->putJson('/testgroep/antwoord/summary',$this->answer(['version'=>$i+1,'summary'=>$this->reflection(['return_choice'=>$choice])]))->assertOk();
  $this->assertSame('no',$p->answers()->where('task','summary')->first()->summary['return_choice']);
  Http::assertNothingSent();
 }
 public function test_round_cannot_finish_without_new_final_step_and_summary_is_exported_safely():void{
  [$p]=$this->person();$this->asPerson($p);
  foreach(array_diff(array_keys(BetaTests::TASKS),['summary']) as $key)$this->putJson('/testgroep/antwoord/'.$key,$this->answer(['outcome'=>'skipped']))->assertOk();
  $this->postJson('/testgroep/afronden',['participant_id'=>$p->id])->assertUnprocessable();
  $this->putJson('/testgroep/antwoord/summary',$this->answer(['summary'=>$this->reflection(['good'=>'=1+1','return_choice'=>'maybe','return_reason'=>'Als de navigatie duidelijker wordt.','improvement'=>'<script>unsafe()</script>'])]))->assertOk();
  $this->postJson('/testgroep/afronden',['participant_id'=>$p->id])->assertOk();
  $this->actingAs($this->admin())->withSession(['auth.password_confirmed_at'=>now()->timestamp])->get('/beheer/testgroep')->assertOk()->assertSee('Misschien')->assertSee('Als de navigatie duidelijker wordt.')->assertDontSee('<script>unsafe()</script>',false);
  $csv=$this->get('/beheer/testgroep/export')->assertOk()->streamedContent();
  $this->assertStringContainsString("'=1+1",$csv);$this->assertStringContainsString('Als de navigatie duidelijker wordt.',$csv);$this->assertStringContainsString('Misschien',$csv);
 }
 public function test_existing_finished_testers_can_add_summary_without_losing_previous_answers():void{
  [$p]=$this->person();$this->asPerson($p)->putJson('/testgroep/antwoord/first',$this->answer(['comment'=>'Bestaand antwoord behouden']))->assertOk();
  $p->update(['completed_at'=>now()]);
  $migration=require database_path('migrations/2026_10_23_160000_add_summary_to_beta_answers.php');$migration->down();$migration->up();
  $this->assertNull($p->fresh()->completed_at);$this->assertDatabaseHas('beta_answers',['participant_id'=>$p->id,'comment'=>'Bestaand antwoord behouden']);
  $this->putJson('/testgroep/antwoord/summary',$this->answer(['summary'=>$this->reflection()]))->assertOk();
 }

}
