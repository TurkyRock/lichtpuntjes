<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Queue};
use Illuminate\Support\Str;
use App\Models\{Article,Comment,PositiveMoment,User};
use App\Services\{ContributionTranslator,GeminiJson,MomentModerator};
use App\Jobs\TranslateContribution;
class ContributionTranslationsTest extends TestCase {
    use RefreshDatabase;
    private function member():User{return User::forceCreate(['name'=>'Vriendelijke schrijver','email'=>Str::uuid().'@example.test','password'=>'Long enough password phrase','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
    private function moment(array $data=[]):PositiveMoment{return PositiveMoment::create($data+['user_id'=>$this->member()->id,'body'=>'De buurvrouw bracht koffie.','status'=>'approved','visibility'=>'public','moment_date'=>now()->toDateString(),'ai_accepted_at'=>now()]);}
    private function article():Article{return Article::create(['fingerprint'=>(string)Str::uuid(),'source_title'=>'Good news','source_name'=>'Bron','source_url'=>'https://example.test/story','origin'=>'news','category'=>'people','status'=>'published','nl'=>['title'=>'Goed nieuws','teaser'=>'Fijn','body'=>['Goed nieuws.']],'en'=>['title'=>'Good news','teaser'=>'Lovely','body'=>['Good news.']]]);}
    private function translate(PositiveMoment|Comment $m,array $answer,string $kind='moment'):void {
        $this->mock(GeminiJson::class,fn($mock)=>$mock->shouldReceive('ask')->once()->andReturn($answer));
        app(ContributionTranslator::class)->translate($kind,$m->id,hash('sha256',$m->body));
    }
    public function test_private_translations_are_encrypted_and_never_enter_public_views():void {
        $m=$this->moment(['visibility'=>'private','body'=>'Een geheim fijn moment.']);
        $this->translate($m,['source_language'=>'nl','nl'=>'AI rewrite','en'=>'A secret lovely moment.']);
        $m->refresh();$this->assertSame('Een geheim fijn moment.',$m->body);$this->assertSame('Een geheim fijn moment.',$m->bodyFor('nl'));$this->assertSame('A secret lovely moment.',$m->bodyFor('en'));
        $this->assertStringNotContainsString('secret lovely',DB::table('positive_moments')->where('id',$m->id)->value('translations'));
        $this->get('/momentjes?lang=en')->assertOk()->assertDontSee('A secret lovely moment.');
        $this->actingAs($m->user)->get('/mijn-momentjes?lang=en')->assertOk()->assertSee('A secret lovely moment.');
        $this->actingAs($this->member())->get('/mijn-momentjes?lang=en')->assertDontSee('A secret lovely moment.');
    }
    public function test_english_original_is_preserved_and_public_page_uses_selected_language():void {
        $m=$this->moment(['body'=>'My neighbour brought coffee.']);
        $this->translate($m,['source_language'=>'en','nl'=>'Mijn buurvrouw bracht koffie.','en'=>'Unwanted rewrite']);
        $this->assertSame('My neighbour brought coffee.',$m->fresh()->bodyFor('en'));
        $this->get('/momentjes?lang=nl')->assertOk()->assertSee('Mijn buurvrouw bracht koffie.')->assertDontSee('Unwanted rewrite');
        $this->get('/momentjes?lang=en')->assertOk()->assertSee('My neighbour brought coffee.');
        $this->get('/en')->assertOk()->assertSee('My neighbour brought coffee.');
    }
    public function test_approval_queues_translation_but_rejection_does_not():void {
        Queue::fake();$m=$this->moment(['status'=>'pending']);
        $this->mock(GeminiJson::class,fn($mock)=>$mock->shouldReceive('ask')->once()->andReturn(['decision'=>'approve','confidence'=>.99,'reason_nl'=>'Vriendelijk','reason_en'=>'Kind']));
        app(MomentModerator::class)->review($m,1);
        Queue::assertPushed(TranslateContribution::class,fn($job)=>$job->kind==='moment'&&$job->id===$m->id);
        $rejected=$this->moment(['status'=>'rejected']);ContributionTranslator::queue('moment',$rejected->id);
        Queue::assertPushed(TranslateContribution::class,1);
    }
    public function test_stale_job_cannot_translate_a_changed_or_rejected_text():void {
        $m=$this->moment();$old=hash('sha256',$m->body);$m->update(['body'=>'Een nieuwe tekst.','status'=>'pending']);
        $this->mock(GeminiJson::class,fn($mock)=>$mock->shouldNotReceive('ask'));
        app(ContributionTranslator::class)->translate('moment',$m->id,$old);$this->assertNull($m->fresh()->translations);
    }
    public function test_edit_during_translation_cannot_overwrite_newer_content():void {
        $m=$this->moment();$hash=hash('sha256',$m->body);
        $this->mock(GeminiJson::class,function($mock)use($m){$mock->shouldReceive('ask')->once()->andReturnUsing(function()use($m){$m->update(['body'=>'Mijn nieuwe momentje.','status'=>'pending','revision'=>2]);return ['source_language'=>'nl','nl'=>'De buurvrouw bracht koffie.','en'=>'An obsolete translation.'];});});
        app(ContributionTranslator::class)->translate('moment',$m->id,$hash);$this->assertNull($m->fresh()->translations);$this->assertSame('Mijn nieuwe momentje.',$m->fresh()->bodyFor('en'));
    }
    public function test_invalid_response_keeps_original_and_job_failure_does_not_expose_private_details():void {
        $m=$this->moment(['visibility'=>'private']);
        $this->mock(GeminiJson::class,fn($mock)=>$mock->shouldReceive('ask')->once()->andReturn(['source_language'=>'nl','nl'=>'','en'=>'']));
        try{app(ContributionTranslator::class)->translate('moment',$m->id,hash('sha256',$m->body));$this->fail('Invalid response accepted');}catch(\Illuminate\Validation\ValidationException $e){}
        (new TranslateContribution('moment',$m->id,hash('sha256',$m->body)))->failed(new \RuntimeException('PRIVATE_PROVIDER_DETAILS'));
        $this->assertSame('failed',$m->fresh()->translation_state);$this->assertSame('De buurvrouw bracht koffie.',$m->fresh()->bodyFor('en'));
        $this->get('/momentjes?lang=en')->assertDontSee('PRIVATE_PROVIDER_DETAILS')->assertDontSee('De buurvrouw bracht koffie.');
    }
    public function test_comment_and_reply_parent_are_readable_in_both_languages():void {
        $article=$this->article();$u=$this->member();$parent=Comment::create(['article_id'=>$article->id,'user_id'=>$u->id,'body'=>'What lovely news!','status'=>'approved']);
        $this->translate($parent,['source_language'=>'en','nl'=>'Wat een mooi nieuws!','en'=>'Wrong rewrite'],'comment');
        $this->getJson('/news/'.$article->id.'/comments?lang=nl')->assertOk()->assertJsonPath('comments.0.body','Wat een mooi nieuws!');
        $this->getJson('/news/'.$article->id.'/comments?lang=en')->assertJsonPath('comments.0.body','What lovely news!');
        $child=Comment::create(['article_id'=>$article->id,'user_id'=>$u->id,'parent_id'=>$parent->id,'body'=>'Helemaal mee eens!','status'=>'approved']);
        $this->getJson('/news/'.$article->id.'/comments?lang=nl&parent_id='.$parent->id)->assertJsonPath('comments.0.parent.body','Wat een mooi nieuws!');
    }
    public function test_backfill_only_includes_public_approved_contributions_by_default():void {
        Queue::fake();$public=$this->moment();$private=$this->moment(['visibility'=>'private']);$rejected=$this->moment(['status'=>'rejected']);
        $this->artisan('community:translate',['--limit'=>50])->assertExitCode(0);Queue::assertPushed(TranslateContribution::class,fn($job)=>$job->id===$public->id);Queue::assertPushed(TranslateContribution::class,1);
        Queue::fake();$this->artisan('community:translate',['--include-private'=>true])->assertExitCode(0);Queue::assertPushed(TranslateContribution::class,2);
    }
    public function test_ready_translation_is_idempotent_and_does_not_award_points():void {
        $m=$this->moment();$this->translate($m,['source_language'=>'nl','nl'=>$m->body,'en'=>'The neighbour brought coffee.']);
        $this->mock(GeminiJson::class,fn($mock)=>$mock->shouldNotReceive('ask'));app(ContributionTranslator::class)->translate('moment',$m->id,hash('sha256',$m->body));
        $this->assertDatabaseCount('lightpoint_events',0);
    }
}
