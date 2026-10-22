<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use App\Models\ProfilePhoto;
use App\Services\ProfilePhotoModerator;
use App\Services\PortraitNormalizer;
use App\Jobs\ReviewProfilePhoto;
class ProfilePhotosAndUsersTest extends TestCase {
 use RefreshDatabase;
 private function member(array $extra=[]):User {return User::forceCreate(array_merge(['name'=>'Vriendelijke bezoeker','email'=>bin2hex(random_bytes(6)).'@example.test','password'=>'LongTestPassword123','email_verified_at'=>now(),'community_accepted_at'=>now()],$extra));}
 private function photo(User $u,array $extra=[]):ProfilePhoto {Storage::disk('local')->put('profile-photos/test-'.$u->id.'.jpg','processed-photo');return ProfilePhoto::create(array_merge(['user_id'=>$u->id,'path'=>'profile-photos/test-'.$u->id.'.jpg','status'=>'pending','self_confirmed_at'=>now()],$extra));}
 public function test_user_management_is_private_and_has_paginated_list_and_detail():void {
  $u=$this->member();$this->get('/beheer/gebruikers')->assertRedirect('/inloggen');$this->actingAs($u)->get('/beheer/gebruikers')->assertForbidden();$this->get('/beheer/gebruikers/'.$u->id)->assertForbidden();
  $admin=$this->member(['is_admin'=>true]);$this->actingAs($admin)->get('/beheer/gebruikers')->assertOk()->assertSee($u->email)->assertHeader('X-Robots-Tag','noindex');$this->get('/beheer/gebruikers/'.$u->id)->assertOk()->assertSee('Lichtpunten en activiteit');$this->get('/beheer/gebruikers/'.$u->id.'?lang=en')->assertOk()->assertSee('Profile photo reviews');
 }
 public function test_unapproved_photos_are_private_even_with_known_id():void {
  Storage::fake('local');$u=$this->member();$p=$this->photo($u);$this->get('/profielfotos/'.$p->id)->assertNotFound();$this->actingAs($this->member())->get('/profiel/foto/'.$p->id)->assertForbidden();$this->actingAs($u)->get('/profiel/foto/'.$p->id)->assertOk()->assertHeader('Cache-Control','no-store, private');
 }
 public function test_upload_requires_self_declaration_and_processing_agreement_and_is_queued():void {
  Storage::fake('local');Queue::fake();$u=$this->member();$this->actingAs($u);
  $upload=fn()=>new UploadedFile(base_path('tests/Fixtures/ai-image.png'),'portrait.png','image/png',null,true);
  $this->post('/profiel/foto',['photo'=>$upload()])->assertSessionHasErrors(['self_photo','photo_processing']);
  $this->mock(PortraitNormalizer::class,fn($m)=>$m->shouldReceive('jpeg')->once()->andReturn('normalized-jpeg'));
  $this->post('/profiel/foto',['photo'=>$upload(),'self_photo'=>1,'photo_processing'=>1])->assertRedirect();$p=ProfilePhoto::firstOrFail();$this->assertSame('pending',$p->status);$this->assertNull($u->fresh()->profile_photo_id);Storage::disk('local')->assertExists($p->path);Queue::assertPushed(ReviewProfilePhoto::class,fn($j)=>$j->photoId===$p->id);
 }
 public function test_avatar_and_script_file_uploads_are_not_accepted_as_files():void {
  $this->actingAs($this->member())->post('/profiel/foto',['photo'=>UploadedFile::fake()->create('avatar.svg',1,'image/svg+xml'),'self_photo'=>1,'photo_processing'=>1])->assertSessionHasErrors('photo');$this->assertDatabaseCount('profile_photos',0);
 }
 public function test_ai_approval_sends_only_processed_image_and_makes_it_public():void {
  Storage::fake('local');$u=$this->member();$p=$this->photo($u);config(['news.gemini_key'=>'test-key','news.gemini_model'=>'test-model']);
  Http::fake(['*'=>Http::response(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode(['decision'=>'approve','confidence'=>.98,'reason'=>'Veilig echt portret.'])]]]]]])]);
  app(ProfilePhotoModerator::class)->review($p);$this->assertSame('approved',$p->fresh()->status);$this->assertSame($p->id,$u->fresh()->profile_photo_id);Http::assertSent(fn($r)=>$r['contents'][0]['parts'][1]['inlineData']['data']===base64_encode('processed-photo')&&!str_contains(json_encode($r->data()),$u->email));$this->get('/profielfotos/'.$p->id)->assertOk()->assertHeader('Content-Type','image/jpeg')->assertHeader('X-Robots-Tag','noindex');
 }
 public function test_provider_failure_and_uncertain_response_never_publish_photo():void {
  Storage::fake('local');$u=$this->member();$p=$this->photo($u);config(['news.gemini_key'=>'test','news.gemini_model'=>'test']);Http::fake(['*'=>Http::response([],429)]);app(ProfilePhotoModerator::class)->review($p);$this->assertSame('doubt',$p->fresh()->status);$this->assertNull($u->fresh()->profile_photo_id);$this->get('/profielfotos/'.$p->id)->assertNotFound();
 }
 public function test_rejected_replacement_does_not_remove_previous_approved_photo():void {
  Storage::fake('local');$u=$this->member();$old=$this->photo($u,['status'=>'approved']);$u->forceFill(['profile_photo_id'=>$old->id])->save();$p=$this->photo($u,['path'=>'profile-photos/replacement.jpg']);Storage::disk('local')->put($p->path,'new');app(ProfilePhotoModerator::class)->decide($p,'rejected','Geen echt portret.',$this->member(['is_admin'=>true]));$this->assertSame($old->id,$u->fresh()->profile_photo_id);$this->assertNull($p->fresh()->path);Storage::disk('local')->assertMissing('profile-photos/replacement.jpg');
 }
 public function test_stale_job_cannot_replace_newer_upload_and_removed_photo_stays_removed():void {
  Storage::fake('local');$u=$this->member();$first=$this->photo($u);$second=$this->photo($u);app(ProfilePhotoModerator::class)->decide($first,'approved','Goed.');$this->assertNull($u->fresh()->profile_photo_id);app(ProfilePhotoModerator::class)->decide($second,'approved','Goed.');$this->actingAs($u)->delete('/profiel/foto')->assertRedirect();$this->assertNull($u->fresh()->profile_photo_id);$this->get('/profielfotos/'.$second->id)->assertNotFound();app(ProfilePhotoModerator::class)->decide($second,'approved','Late job.');$this->assertNull($u->fresh()->profile_photo_id);
 }
 public function test_suspension_is_logged_and_hides_photo_and_blocks_participation():void {
  Storage::fake('local');$u=$this->member();$p=$this->photo($u,['status'=>'approved']);$u->forceFill(['profile_photo_id'=>$p->id])->save();$admin=$this->member(['is_admin'=>true]);$this->actingAs($admin)->post('/beheer/users/'.$u->id,['suspended'=>1,'note'=>'Herhaaldelijke persoonlijke aanvallen.'])->assertRedirect();$this->assertTrue($u->fresh()->is_suspended);$this->assertDatabaseHas('user_moderation_events',['user_id'=>$u->id,'action'=>'suspended']);$this->get('/profielfotos/'.$p->id)->assertNotFound();$this->actingAs($u->fresh())->get('/profiel')->assertForbidden();$this->actingAs($admin)->post('/beheer/users/'.$admin->id,['suspended'=>1])->assertUnprocessable();$this->post('/beheer/users/'.$u->id,['suspended'=>0,'note'=>'Afspraken gemaakt.'])->assertRedirect();$this->assertFalse($u->fresh()->is_suspended);
 }
 public function test_photo_moderation_requires_admin_and_portrait_confirmation():void {
  Storage::fake('local');$u=$this->member();$p=$this->photo($u);$this->actingAs($u)->post('/beheer/profielfotos/'.$p->id,['decision'=>'approved','note'=>'Test','portrait_confirmed'=>1])->assertForbidden();$this->actingAs($this->member(['is_admin'=>true]))->post('/beheer/profielfotos/'.$p->id,['decision'=>'approved','note'=>'Test'])->assertSessionHasErrors('portrait_confirmed');$this->get('/beheer/gebruikers/'.$u->id)->assertOk()->assertSee('Ingestuurd portret');
 }
 public function test_expired_private_files_are_cleaned_without_removing_active_photo():void {
  Storage::fake('local');$u=$this->member();$p=$this->photo($u);$this->travel(31)->days();$this->artisan('profiles:cleanup')->assertExitCode(0);$this->assertNull($p->fresh()->path);Storage::disk('local')->assertMissing('profile-photos/test-'.$u->id.'.jpg');
 }
 public function test_profile_and_privacy_explain_photo_processing_in_both_languages():void {
  $u=$this->member();$this->actingAs($u)->get('/profiel')->assertOk()->assertSee('Een gezicht bij je naam')->assertSee('Google Gemini');$this->get('/profiel?lang=en')->assertOk()->assertSee('A face with your name');$this->get('/privacy?lang=en')->assertOk()->assertSee('Optional profile photo');
 }
}
