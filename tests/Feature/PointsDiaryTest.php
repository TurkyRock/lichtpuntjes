<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Services\PointsDiary;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http,File};
class PointsDiaryTest extends TestCase {
 use RefreshDatabase;
 private int $sequence=0;
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();$this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00','Europe/Amsterdam'));}
 private function person():User {return User::forceCreate(['name'=>'Zonnetje','email'=>uniqid().'@example.test','password'=>'Password12345','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
 private function event(User $u,string $day,string $kind,int $points=1,bool $active=true,string $label='Activiteit'):void {DB::table('lightpoint_events')->insert(['user_id'=>$u->id,'reference'=>'diary:'.++$this->sequence,'kind'=>$kind,'points'=>$points,'active'=>$active,'label'=>$label,'period'=>$day,'created_at'=>now(),'updated_at'=>now()]);}
 public function test_full_days_are_grouped_without_old_twenty_five_event_limit_and_private_totals_stay_accurate():void {
  $u=$this->person();$other=$this->person();
  for($i=0;$i<40;$i++)$this->event($u,'2026-10-22','read');
  $this->event($u,'2026-10-22','heart');$this->event($u,'2026-10-22','return');$this->event($u,'2026-10-22','comment',10,false);
  $this->event($u,'2026-10-21','invite',5);$this->event($u,'2026-10-18','adjustment',-2,true,'Eigen correctie');
  $this->event($other,'2026-10-22','adjustment',1000,true,'PRIVATE_OTHER_LABEL');
  $before=DB::table('lightpoint_events')->count();$this->actingAs($u);
  foreach(['nl','en'] as $lang){$r=$this->get('/profiel?section=points&lang='.$lang)->assertOk()->assertSee('points-diary')->assertSee($lang==='nl'?'40 artikelen gelezen':'40 articles read')->assertSee($lang==='nl'?'Ingetrokken · telt niet mee':'Withdrawn · does not count')->assertDontSee('PRIVATE_OTHER_LABEL')->assertDontSee('paper-points-table');$r->assertViewHas('progress',fn($p)=>$p['points']===45)->assertViewHas('pointsDiary',fn($d)=>$d['today']===42&&$d['days']->total()===3&&$d['days']->first()['points']===42);$this->assertSame(3,substr_count($r->getContent(),'data-diary-date='));
   if(getenv('DIARY_CAPTURE')){File::ensureDirectoryExists(base_path('../verification/diary-render'));File::put(base_path('../verification/diary-render/diary-'.$lang.'.html'),$r->getContent());}
  }
  $this->assertDatabaseCount('lightpoint_events',$before);Http::assertNothingSent();
 }
 public function test_pagination_keeps_each_day_whole_and_keeps_language_and_account_section():void {
  $u=$this->person();for($i=0;$i<7;$i++){for($j=0;$j<4;$j++)$this->event($u,now('Europe/Amsterdam')->subDays($i)->toDateString(),'read');}
  $this->actingAs($u)->get('/profiel?section=points&lang=en')->assertOk()->assertSee('4 articles read')->assertSee('page=2',false)->assertSee('section=points',false)->assertSee('lang=en',false)->assertDontSee('data-diary-date="2026-10-19"',false);
  $this->get('/profiel?section=points&lang=en&page=2')->assertOk()->assertSee('data-diary-date="2026-10-19"',false)->assertDontSee('data-diary-date="2026-10-22"',false)->assertViewHas('pointsDiary',fn($d)=>$d['days']->count()===3);
  $this->get('/profiel?section=points&lang=en&page=3')->assertOk()->assertViewHas('pointsDiary',fn($d)=>$d['days']->count()===1);
 }
 public function test_local_period_and_unknown_adjustments_are_preserved_and_untrusted_labels_are_escaped():void {
  $u=$this->person();$this->event($u,'2026-10-22','moment',1,true,'Een momentje om te bewaren');$this->event($u,'2026-10-22','moment',1,true,'Een fijn moment om te bewaren');
  $this->event($u,'2025-12-31','adjustment',-1,true,'<script>PRIVATE_MARKER</script>');$this->event($u,'2025-12-31','adjustment',2,true,'Andere correctie');
  $d=app(PointsDiary::class)->forUser($u);$this->assertSame(2,$d['today']);$this->assertCount(1,$d['days']->first()['groups']);$this->assertSame(2,$d['days']->first()['groups'][0]['count']);$this->assertCount(2,$d['days']->last()['groups']);
  $this->actingAs($u)->get('/profiel?section=points')->assertOk()->assertSee('2025')->assertSee('&lt;script&gt;PRIVATE_MARKER&lt;/script&gt;',false)->assertDontSee('<script>PRIVATE_MARKER</script>',false);
 }
 public function test_empty_diary_is_styled_and_account_specific():void {
  $this->get('/profiel?section=points')->assertRedirect('/inloggen');$u=$this->person();
  foreach(['nl','en'] as $lang){$r=$this->actingAs($u)->get('/profiel?section=points&lang='.$lang)->assertOk()->assertSee('diary-empty')->assertSee($lang==='nl'?'Ontdek de lichtpuntjes':'Discover the stories')->assertViewHas('pointsDiary',fn($d)=>$d['today']===0&&$d['days']->isEmpty());
   if(getenv('DIARY_CAPTURE'))File::put(base_path('../verification/diary-render/diary-empty-'.$lang.'.html'),$r->getContent());
  }
  $this->get('/profiel?section=invite')->assertOk()->assertDontSee('points-diary.css')->assertSee('invite-studio');
 }
}
