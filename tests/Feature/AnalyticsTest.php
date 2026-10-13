<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_has_only_first_party_gate_and_default_denial(): void
    {
        $r=$this->get('/')->assertOk()->assertSee('G-3JSRH397WV')->assertSee('/js/analytics.js',false)->assertSee('"allowed":false',false);
        $this->assertDoesNotMatchRegularExpression('/<script[^>]+src="https:\/\/www\.googletagmanager\.com/', $r->getContent());
        $this->assertStringContainsString('https://*.google-analytics.com',$r->headers->get('Content-Security-Policy'));
    }

    public function test_only_current_permission_enables_gate(): void
    {
        $this->withCredentials()->withCookie('lichtpunt_consent','v2|yes|'.now()->timestamp)
            ->get('/')->assertOk()->assertSee('"allowed":true',false);
        $this->withCookie('lichtpunt_consent','v1|yes|'.now()->timestamp)
            ->get('/')->assertOk()->assertSee('"allowed":false',false);
        $this->withCookie('lichtpunt_consent','v2|yes|'.now()->subDays(181)->timestamp)
            ->get('/')->assertOk()->assertSee('"allowed":false',false);
    }

    public function test_private_pages_and_public_policy_pages_have_no_google_tag(): void
    {
        foreach(['/inloggen','/aanmelden','/wachtwoord-vergeten','/privacy','/huisregels'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('lichtpuntAnalyticsConfig')->assertDontSee('/js/analytics.js',false);
        }
        $user=User::forceCreate(['name'=>'Private account name','email'=>'private@example.com','password'=>'TestPassword123','email_verified_at'=>now(),'community_accepted_at'=>now()]);
        foreach(['/profiel','/mijn-lichtpuntjes','/lichtpuntje/schrijven'] as $path) {
            $r=$this->actingAs($user)->get($path)->assertOk()->assertDontSee('lichtpuntAnalyticsConfig');
            $this->assertStringNotContainsString('google-analytics.com',$r->headers->get('Content-Security-Policy'));
        }
    }

    public function test_disabled_or_invalid_configuration_is_not_rendered(): void
    {
        config(['analytics.enabled'=>false]);
        $this->get('/')->assertOk()->assertDontSee('lichtpuntAnalyticsConfig');
        config(['analytics.enabled'=>true,'analytics.measurement_id'=>'G-123"<script>']);
        $this->get('/')->assertOk()->assertDontSee('lichtpuntAnalyticsConfig');
    }

    public function test_rejecting_statistics_deletes_host_only_analytics_cookies(): void
    {
        $this->withCredentials()->withUnencryptedCookies(['_ga'=>'GA1.1.test','_ga_3JSRH397WV'=>'test'])
            ->postJson('/cookies/keuze',['statistics'=>false])->assertOk()
            ->assertCookieExpired('_ga')->assertCookieExpired('_ga_3JSRH397WV');
    }
}
