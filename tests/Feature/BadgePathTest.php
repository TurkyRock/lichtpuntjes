<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Http,File};
class BadgePathTest extends TestCase
{
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();Http::preventStrayRequests();}
 public function test_all_twenty_four_badges_are_visible_with_rules_below_and_real_progress():void {
  $u=User::forceCreate(['name'=>'Zonnetje','email'=>'badge@example.test','password'=>'Password12345','email_verified_at'=>now(),'community_accepted_at'=>now()]);
  DB::table('lightpoint_events')->insert(['user_id'=>$u->id,'reference'=>'test:points','kind'=>'read','points'=>136,'label'=>'Voorbeeld','period'=>now()->toDateString(),'active'=>true,'created_at'=>now(),'updated_at'=>now()]);$this->actingAs($u);
  foreach(['nl','en'] as $lang){$r=$this->get('/profiel?section=badges&lang='.$lang)->assertOk()->assertDontSee('class="ladder-collection"',false)->assertSee('aria-current="step"',false);$html=$r->getContent();$this->assertSame(24,substr_count($html,'class="badge-path-step '));$this->assertStringContainsString('>136<',$html);$this->assertStringContainsString('<b>39</b>',$html);$this->assertGreaterThan(strpos($html,'paper-badge--orbit'),strpos($html,'id="badgePointsTitle"'));
   if(getenv('BADGE_CAPTURE')){$dir=base_path('../verification/badge-render');File::ensureDirectoryExists($dir);File::put($dir.'/badges-'.$lang.'.html',$html);}
  }
  $this->get('/profiel')->assertOk()->assertSee('/images/badges/post.svg',false);Http::assertNothingSent();
 }
}
