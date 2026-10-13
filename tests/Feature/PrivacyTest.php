<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class PrivacyTest extends TestCase {
 use RefreshDatabase;
 public function test_notice_and_private_requests():void {
  $this->get('/privacy')->assertOk()->assertSee('Concept voor de testversie');
  $this->get('/privacy?lang=en')->assertOk()->assertSee('Draft for the test version');
  $this->get('/privacy/verzoeken')->assertRedirect('/inloggen');
  $a=User::factory()->create();$b=User::factory()->create();
  $this->actingAs($a)->post('/privacy/verzoeken',['kind'=>'deletion','message'=>'Please remove my account.','user_id'=>$b->id])->assertRedirect();
  $this->assertDatabaseHas('privacy_requests',['user_id'=>$a->id,'kind'=>'deletion']);
  $this->assertDatabaseHas('users',['id'=>$a->id]);
  $this->actingAs($b)->get('/privacy/verzoeken')->assertOk()->assertDontSee('Please remove my account.');
  $this->get('/beheer/privacy')->assertForbidden();
  $this->post('/beheer/privacy/1',['handled'=>1])->assertForbidden();
  $a->forceFill(['is_admin'=>true])->save();
  $this->actingAs($a)->get('/beheer/privacy')->assertOk()->assertSee('Please remove my account.');
  $this->post('/beheer/privacy/1',['handled'=>1])->assertRedirect();
  $this->assertNotNull(DB::table('privacy_requests')->value('resolved_at'));
 }
}
