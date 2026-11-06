<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http, Queue, Storage, File};
use App\Models\{User, PositiveMoment};
use App\Services\{GeminiJson, CloudflareIllustrator, MomentIllustrator, MomentImageFiles, MomentImageBudget, MomentImageStyle, ImageBudgetException};
use App\Jobs\GenerateMomentImage;
class MomentImagesTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp():void { parent::setUp(); Http::preventStrayRequests(); Queue::fake(); Storage::fake('local'); config(['moment-images.enabled'=>true,'news_images.provider'=>'cloudflare']); }
    private function user(array $v=[]):User { return User::forceCreate(array_merge(['name'=>'Vrolijke schrijver','email'=>bin2hex(random_bytes(6)).'@example.test','password'=>'TestPassword123456','email_verified_at'=>now(),'community_accepted_at'=>now()],$v)); }
    private function moment(User $u,array $v=[]):PositiveMoment { return PositiveMoment::create(array_merge(['user_id'=>$u->id,'body'=>'Fijn koffie gedronken in het zonnetje.','status'=>'approved','visibility'=>'private','moment_date'=>now()->toDateString(),'ai_accepted_at'=>now(),'image_accepted_at'=>now(),'image_state'=>'queued'],$v)); }
    private function png():string { $im=imagecreatetruecolor(512,512);imagefill($im,0,0,imagecolorallocate($im,245,220,150));ob_start();imagepng($im);$b=ob_get_clean();imagedestroy($im);return $b; }
    private function ready(PositiveMoment $m):PositiveMoment { $path=app(MomentImageFiles::class)->save($this->png());$m->update(['image_state'=>'ready','image_revision'=>$m->revision,'image_style'=>'sketch','image_path'=>$path]);return $m; }
    private function planning(string $style='sketch'):void {
        $this->mock(MomentImageBudget::class,fn($m)=>$m->shouldReceive('reserve')->once());
        $this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->once()->andReturn(['subject'=>'coffee','style'=>$style]));
    }
    public function test_balancing_limits_skew_and_avoids_three_equal_styles():void {
        $s=new MomentImageStyle;
        $this->assertSame('photo',$s->choose('sketch',['sketch','sketch']));
        $this->assertSame('sketch',$s->choose('photo',['photo','photo','sketch','photo']));
        $this->assertSame('photo',$s->choose('photo',['sketch','photo']));
        $this->assertSame('sketch',$s->choose('sketch',[]));
    }
    public function test_image_consent_is_separate_and_nothing_is_generated_before_review():void {
        $u=$this->user();$this->actingAs($u)->post('/momentjes',['body'=>'Een fijn nieuw momentje.','visibility'=>'private','ai_processing'=>1])->assertRedirect();
        $this->assertNull(PositiveMoment::first()->image_accepted_at);
        $this->post('/momentjes',['body'=>'Een tweede fijn momentje.','visibility'=>'private','ai_processing'=>1,'image_processing'=>1])->assertRedirect();
        $this->assertNotNull(PositiveMoment::latest('id')->first()->image_accepted_at);
        Queue::assertNotPushed(GenerateMomentImage::class);
        $this->post('/momentjes',['body'=>'Een derde fijn momentje.','visibility'=>'private','ai_processing'=>1,'image_processing'=>'bad'])->assertSessionHasErrors('image_processing');
    }
    public function test_existing_moment_image_requires_owner_and_explicit_consent():void {
        $owner=$this->user();$m=$this->moment($owner,['image_accepted_at'=>null,'image_state'=>null]);
        $this->actingAs($this->user())->post('/momentjes/'.$m->id.'/afbeelding',['image_processing'=>1])->assertForbidden();
        $this->actingAs($owner)->post('/momentjes/'.$m->id.'/afbeelding')->assertSessionHasErrors('image_processing');
        $this->post('/momentjes/'.$m->id.'/afbeelding',['image_processing'=>1])->assertRedirect();
        Queue::assertPushed(GenerateMomentImage::class,fn($job)=>$job->momentId===$m->id);
        $this->post('/momentjes/'.$m->id.'/afbeelding',['image_processing'=>1])->assertStatus(409);
    }
    public function test_private_image_is_hidden_from_guests_other_members_and_administrators():void {
        $owner=$this->user();$m=$this->ready($this->moment($owner));$url=$m->momentImageUrl();
        $this->get($url)->assertNotFound();$this->actingAs($this->user())->get($url)->assertNotFound();
        $this->actingAs($this->user(['is_admin'=>true]))->get($url)->assertNotFound();
        $this->actingAs($owner)->get($url)->assertOk()->assertHeader('Cache-Control','no-store, private');
        $this->assertStringNotContainsString($m->image_path,$this->get('/mijn-momentjes')->getContent());
    }
    public function test_public_images_follow_approval_suspension_and_revision():void {
        $u=$this->user();$m=$this->ready($this->moment($u,['visibility'=>'public']));$url=$m->momentImageUrl();$this->get($url)->assertOk();
        $m->update(['status'=>'pending']);$this->get($url)->assertNotFound();$m->update(['status'=>'approved','revision'=>2]);$this->get($url)->assertNotFound();
        $m->update(['revision'=>1]);$u->forceFill(['is_suspended'=>true])->save();$this->get($url)->assertNotFound();
    }
    public function test_edit_and_delete_remove_images_and_old_jobs_cannot_recreate_them():void {
        $u=$this->user();$m=$this->ready($this->moment($u));$path=$m->image_path;
        $this->actingAs($u)->put('/momentjes/'.$m->id,['body'=>'Een ander mooi momentje.','visibility'=>'private','ai_processing'=>1])->assertRedirect();
        Storage::disk('local')->assertMissing($path);$this->assertNull($m->fresh()->image_path);
        $this->mock(GeminiJson::class,fn($mock)=>$mock->shouldNotReceive('ask'));
        app(MomentIllustrator::class)->generate($m->id,1);$this->assertSame(2,$m->fresh()->revision);
        $other=$this->ready($this->moment($u));$path=$other->image_path;$this->delete('/momentjes/'.$other->id)->assertRedirect();Storage::disk('local')->assertMissing($path);
    }
    public function test_generation_only_sends_generic_subject_to_image_provider_and_resizes():void {
        $u=$this->user();$m=$this->moment($u,['body'=>'Met Jan koffie op mijn geheime adres gedronken.']);$this->planning();
        $bytes=$this->png();$this->mock(CloudflareIllustrator::class,fn($mock)=>$mock->shouldReceive('generateSmallMoment')->once()->with(\Mockery::on(fn($p)=>str_contains($p,'ceramic coffee cup')&&!str_contains($p,'Jan')&&!str_contains($p,'geheime')))->andReturn($bytes));
        app(MomentIllustrator::class)->generate($m->id,1);$m->refresh();$this->assertSame('ready',$m->image_state);
        $info=getimagesizefromstring(Storage::disk('local')->get($m->image_path));$this->assertSame([384,384],array_slice($info,0,2));
        $this->assertLessThan(200*1024,Storage::disk('local')->size($m->image_path));
        // An already ready image never spends another provider request.
        app(MomentIllustrator::class)->generate($m->id,1);
    }
    public function test_generation_uses_personal_history_and_excludes_other_users():void {
        $u=$this->user();foreach(range(1,2) as $i)$this->moment($u,['image_state'=>'ready','image_style'=>'sketch']);
        $this->moment($this->user(),['image_state'=>'ready','image_style'=>'photo']);$m=$this->moment($u);$this->planning('sketch');
        $this->mock(CloudflareIllustrator::class,fn($mock)=>$mock->shouldReceive('generateSmallMoment')->once()->andReturn($this->png()));
        app(MomentIllustrator::class)->generate($m->id,1);$this->assertSame('photo',$m->fresh()->image_style);
    }
    public function test_concurrent_edit_during_generation_discards_the_stale_image():void {
        $m=$this->moment($this->user());$this->planning();$bytes=$this->png();
        $this->mock(CloudflareIllustrator::class,fn($mock)=>$mock->shouldReceive('generateSmallMoment')->once()->andReturnUsing(function()use($m,$bytes){$m->update(['revision'=>2,'image_accepted_at'=>null,'image_state'=>null]);return $bytes;}));
        app(MomentIllustrator::class)->generate($m->id,1);$this->assertNull($m->fresh()->image_path);$this->assertSame([],Storage::disk('local')->allFiles('moment-images'));
    }
    public function test_no_consent_no_approval_or_suspended_account_never_calls_ai():void {
        $this->mock(GeminiJson::class,fn($mock)=>$mock->shouldNotReceive('ask'));
        $u=$this->user();foreach([['image_accepted_at'=>null],['status'=>'pending'],['revision'=>2]] as $v){$m=$this->moment($u,$v);app(MomentIllustrator::class)->generate($m->id,1);}
        $u->forceFill(['is_suspended'=>true])->save();$m=$this->moment($u);app(MomentIllustrator::class)->generate($m->id,1);
    }
    public function test_public_polaroid_feed_has_fifteen_entries_and_no_private_content():void {
        $u=$this->user();foreach(range(1,16) as $i)$this->moment($u,['visibility'=>'public','body'=>'Openbaar lichtpuntje nummer '.$i]);
        $this->moment($u,['body'=>'GEHEIME PRIVE TEKST']);
        $r=$this->get('/momentjes?lang=nl');$r->assertOk()->assertDontSee('GEHEIME PRIVE TEKST')->assertSee('id="momentDetail"',false);
        $this->assertSame(15,substr_count($r->getContent(),'class="moment-polaroid"'));
        $this->get('/momentjes?lang=en&page=2')->assertOk()->assertSee('Read this moment')->assertSee('lang=en',false);
    }
    public function test_personal_page_puts_new_form_first_and_supports_both_languages():void {
        $u=$this->user();$this->moment($u);
        $html=$this->actingAs($u)->get('/mijn-momentjes')->assertOk()->getContent();
        $this->assertLessThan(strpos($html,'id="moments-list"'),strpos($html,'id="new-moment"'));
        $this->get('/mijn-momentjes?lang=en')->assertOk()->assertSee('What made your day brighter?')->assertSee('This made you smile');
    }
    public function test_daily_budget_counts_attempts_and_stops_before_more_calls():void {
        $file=storage_path('app/test-moment-budget-'.bin2hex(random_bytes(8)).'.json');
        config(['moment-images.budget_file'=>$file,'moment-images.daily_limit'=>1]);
        try {
            $budget=new MomentImageBudget;$budget->reserve();
            $this->assertSame(1,json_decode(File::get($file),true)['attempts']);
            try{$budget->reserve();$this->fail('Expected budget limit');}catch(ImageBudgetException $e){$this->assertSame(1,json_decode(File::get($file),true)['attempts']);}
            File::put($file,'broken');$this->expectException(\JsonException::class);$budget->reserve();
        }finally{File::delete($file);}
    }
    public function test_small_images_use_the_existing_account_wide_cloudflare_cap():void {
        \Illuminate\Support\Sleep::fake();
        $file=storage_path('app/test-moment-provider-'.bin2hex(random_bytes(8)).'.json');
        config(['news_images.cloudflare_state_file'=>$file,'news_images.cloudflare_account_id'=>str_repeat('a',32),'news_images.cloudflare_token'=>'fake-test-token','news_cycle.cloudflare_attempts'=>1]);
        Http::fake(['api.cloudflare.com/*'=>Http::response(['success'=>true,'result'=>['image'=>base64_encode($this->png())]])]);
        try {
            $provider=app(CloudflareIllustrator::class);$this->assertNotEmpty($provider->generateSmallMoment('A ceramic coffee cup.'));Http::assertSentCount(1);
            try{$provider->generateSmallMoment('A small daisy.');$this->fail('Expected global limit');}catch(ImageBudgetException $e){Http::assertSentCount(1);}
        }finally{File::delete($file);}
    }
    public function test_moderation_queues_an_image_only_when_approved_and_consented():void {
        $u=$this->user();$m=$this->moment($u,['status'=>'pending']);
        $this->mock(GeminiJson::class,fn($mock)=>$mock->shouldReceive('ask')->andReturn(['decision'=>'approve','confidence'=>.99,'reason_nl'=>'Een vriendelijk momentje.','reason_en'=>'A kind moment.']));
        app(\App\Services\MomentModerator::class)->review($m,1);
        Queue::assertPushed(GenerateMomentImage::class,fn($job)=>$job->momentId===$m->id&&$job->queue==='moment-images');
        $noConsent=$this->moment($u,['status'=>'pending','image_accepted_at'=>null]);app(\App\Services\MomentModerator::class)->review($noConsent,1);
        Queue::assertPushed(GenerateMomentImage::class,1);
    }
    public function test_capture_design_when_requested():void {
        if (!getenv('MOMENTS_CAPTURE')) { $this->assertTrue(true); return; }
        $this->travelTo(\Carbon\Carbon::parse('2026-10-22 12:00:00','Europe/Amsterdam'));
        $u=$this->user();$other=$this->user(['name'=>'Sanne']);
        $path=app(MomentImageFiles::class)->save(file_get_contents(public_path('images/share-choice-coffee.png')));
        $examples=['Wat een heerlijk zonnige dag vandaag!','Een kop koffie in de ochtendzon.','Een onverwacht lief berichtje ontvangen.'];
        foreach(range(1,16) as $i)$this->moment($i<=3?$u:$other,['body'=>$examples[($i-1)%3], 'visibility'=>$i===1?'private':'public', 'image_state'=>'ready', 'image_path'=>$path, 'image_style'=>$i%2?'sketch':'photo', 'image_revision'=>1]);
        $dir=base_path('../verification/moment-render');@mkdir($dir,0777,true);
        foreach(['nl','en'] as $lang){
            $this->actingAs($u);
            file_put_contents($dir.'/account-'.$lang.'.html',$this->get('/mijn-momentjes?lang='.$lang)->assertOk()->getContent());
            file_put_contents($dir.'/gallery-'.$lang.'.html',$this->get('/momentjes?lang='.$lang)->assertOk()->getContent());
        }
        file_put_contents($dir.'/sample.webp',Storage::disk('local')->get($path));
        $this->get('/')->assertOk();
        file_put_contents($dir.'/home.html',$this->get('/')->getContent());
    }
}
