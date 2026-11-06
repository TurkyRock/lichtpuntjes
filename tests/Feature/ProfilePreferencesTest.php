<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,ProfilePhoto};
use App\Rules\FriendlyDisplayName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http,Validator};
class ProfilePreferencesTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp():void {parent::setUp();Http::preventStrayRequests();}
    private function member():User {return User::forceCreate(['name'=>'Erik','email'=>'erik@example.test','password'=>'TestPassword12345','email_verified_at'=>now(),'community_accepted_at'=>now()]);}
    public function test_friendly_names_are_accepted_and_common_obfuscations_rejected():void
    {
        foreach(['Erik Turkesteen','Dick','Dickinson','Anne-Marie','José','Scunthorpe','Lichtpuntje42'] as $name) $this->assertTrue(Validator::make(['name'=>$name],['name'=>FriendlyDisplayName::rules()])->passes(),$name);
        foreach(['Klootzak','k.l.o.o.t.z.a.k','KLOOTZAK99','idi00t','fuckyou','Ik haat iedereen','bitch_123','shithead'] as $name) $this->assertFalse(Validator::make(['name'=>$name],['name'=>FriendlyDisplayName::rules()])->passes(),$name);
    }
    public function test_member_can_change_only_their_own_name_and_bad_name_is_not_saved():void
    {
        $u=$this->member();$this->actingAs($u)->post('/profiel/naam',['name'=>'  Erik Zon  ','is_admin'=>true])->assertRedirect('/privacy/verzoeken?lang=nl');
        $this->assertSame('Erik Zon',$u->fresh()->name);$this->assertFalse($u->fresh()->is_admin);
        $this->post('/profiel/naam',['name'=>'Klootzak'])->assertSessionHasErrors('name');$this->assertSame('Erik Zon',$u->fresh()->name);Http::assertNothingSent();
    }
    public function test_guests_cannot_change_a_name_and_registration_checks_names():void
    {
        $this->post('/profiel/naam',['name'=>'Erik'])->assertRedirect('/inloggen');
        $this->post('/aanmelden',['name'=>'Fuckyou','email'=>'new@example.test','password'=>'TestPassword12345','password_confirmation'=>'TestPassword12345','intentions'=>'1'])->assertSessionHasErrors('name');
        $this->assertDatabaseCount('users',0);
    }
    public function test_photo_prompt_moves_from_overview_to_my_data_after_upload():void
    {
        $u=$this->member();$this->actingAs($u)->get('/profiel')->assertOk()->assertSee('Een gezicht bij je naam');
        $photo=ProfilePhoto::create(['user_id'=>$u->id,'status'=>'pending','path'=>'profile-photos/test.jpg','self_confirmed_at'=>now()]);
        $this->get('/profiel')->assertOk()->assertDontSee('Een gezicht bij je naam');
        $this->get('/privacy/verzoeken')->assertOk()->assertSee('Een gezicht bij je naam')->assertSee('Gebruikersnaam opslaan');
        $photo->update(['status'=>'approved']);$u->forceFill(['profile_photo_id'=>$photo->id])->save();
        $this->get('/profiel')->assertOk()->assertDontSee('Een gezicht bij je naam');
        $this->get('/privacy/verzoeken?lang=en')->assertOk()->assertSee('Save username')->assertSee('A face with your name');
    }
    public function test_new_moment_sharing_is_first_and_selected_while_existing_privacy_is_preserved():void
    {
        $html=view('community.moment-form',['en'=>false])->render();
        $this->assertLessThan(strpos($html,'value="private"'),strpos($html,'value="public"'));
        $this->assertMatchesRegularExpression('/value="public"\s+checked/',$html);
        $editing=new \App\Models\PositiveMoment(['id'=>1,'body'=>'Mijn moment','visibility'=>'private']);
        $html=view('community.moment-form',['en'=>false,'editing'=>$editing])->render();
        $this->assertMatchesRegularExpression('/value="private"\s+checked/',$html);
    }
}
