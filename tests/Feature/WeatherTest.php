<?php

namespace Tests\Feature;

use App\Services\WeatherForecast;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00:00', 'Europe/Amsterdam'));
        Cache::flush();
        Http::preventStrayRequests();
    }

    private function payload(): array
    {
        $daily = ['time'=>[], 'temperature_2m_max'=>[], 'temperature_2m_min'=>[], 'precipitation_sum'=>[], 'sunshine_duration'=>[], 'daylight_duration'=>[], 'sunrise'=>[], 'sunset'=>[], 'weather_code'=>[]];
        $hourly = ['time'=>[], 'temperature_2m'=>[], 'precipitation_probability'=>[], 'precipitation'=>[], 'sunshine_duration'=>[], 'weather_code'=>[]];
        for ($i=0; $i<7; $i++) {
            $date = CarbonImmutable::now('Europe/Amsterdam')->addDays($i)->toDateString();
            foreach (['time'=>$date, 'temperature_2m_max'=>8, 'temperature_2m_min'=>2, 'precipitation_sum'=>3, 'sunshine_duration'=>7200, 'daylight_duration'=>28800, 'sunrise'=>$date.'T08:00', 'sunset'=>$date.'T16:00', 'weather_code'=>61] as $key=>$value) $daily[$key][] = $value;
            foreach (['time'=>$date.'T12:00', 'temperature_2m'=>7, 'precipitation_probability'=>75, 'precipitation'=>0.2, 'sunshine_duration'=>1200, 'weather_code'=>61] as $key=>$value) $hourly[$key][] = $value;
        }
        return ['current'=>['time'=>'2026-11-10T12:00', 'temperature_2m'=>7, 'weather_code'=>2, 'is_day'=>1], 'daily'=>$daily, 'hourly'=>$hourly];
    }

    public function test_page_and_navigation_render_in_both_languages_without_api_call(): void
    {
        foreach (['nl'=>'Hang wat licht aan je dag', 'en'=>'Hang a little light in your day'] as $lang=>$heading) {
            $this->get('/weer?lang='.$lang)->assertOk()->assertSee($heading)->assertSee('data-weather-link', false)->assertDontSee('data-nav-nl="Mijn verhaal"', false);
        }
        Http::assertNothingSent();
    }

    public function test_seven_days_hourly_values_and_cache(): void
    {
        Http::fake(['api.open-meteo.com/*'=>Http::response($this->payload())]);
        $this->getJson('/weer/data?city=arnhem')->assertOk()->assertJsonCount(7, 'days')->assertJsonPath('days.0.light_fraction', 0.25)->assertJsonPath('days.0.hours.0.rain_probability', 75)->assertJsonPath('current.temperature', 7)->assertJsonPath('stale', false);
        $this->getJson('/weer/data?city=arnhem')->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn($r)=>$r['timezone']==='Europe/Amsterdam' && $r['forecast_days']===7);
    }

    public function test_city_validation_and_cache_isolation(): void
    {
        Http::fake(['api.open-meteo.com/*'=>Http::response($this->payload())]);
        $this->getJson('/weer/data?city=unknown')->assertUnprocessable();
        $this->getJson('/weer/data?city[]=arnhem')->assertUnprocessable();
        Http::assertNothingSent();
        $this->getJson('/weer/data?city=arnhem')->assertOk()->assertJsonPath('name', 'Arnhem');
        $this->getJson('/weer/data?city=utrecht')->assertOk()->assertJsonPath('name', 'Utrecht');
        Http::assertSentCount(2);
    }

    public function test_null_values_stay_unknown_instead_of_becoming_zero(): void
    {
        $data=$this->payload();
        $data['daily']['sunshine_duration'][0]=null;
        $data['hourly']['precipitation_probability'][0]=null;
        $data['current']['temperature_2m']=null;
        Http::fake(['api.open-meteo.com/*'=>Http::response($data)]);
        $this->getJson('/weer/data')->assertOk()->assertJsonPath('days.0.sun', null)->assertJsonPath('days.0.light_fraction', null)->assertJsonPath('days.0.hours.0.rain_probability', null)->assertJsonPath('current.temperature', null);
    }

    public function test_outage_uses_recent_cached_data_then_expires_and_limits_retries(): void
    {
        Http::fake(['api.open-meteo.com/*'=>Http::sequence()->push($this->payload())->push([], 503)->push([], 503)]);
        $service=app(WeatherForecast::class);
        $this->assertFalse($service->get('arnhem')['stale']);
        $this->travel(21)->minutes();
        $this->getJson('/weer/data')->assertOk()->assertJsonPath('stale', true);
        $this->getJson('/weer/data')->assertOk()->assertJsonPath('stale', true);
        Http::assertSentCount(2);
        $this->travel(2)->hours();
        $this->getJson('/weer/data')->assertStatus(503)->assertJsonPath('unavailable', true);
    }

    public function test_malformed_response_is_not_cached_as_a_forecast(): void
    {
        Http::fake(['api.open-meteo.com/*'=>Http::response(['daily'=>['time'=>[]]])]);
        $this->getJson('/weer/data')->assertStatus(503);
        $this->getJson('/weer/data')->assertStatus(503);
        Http::assertSentCount(1);
    }

    public function test_yesterdays_cache_is_not_presented_as_today(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 23:50:00', 'Europe/Amsterdam'));
        Http::fake(['api.open-meteo.com/*'=>Http::sequence()->push($this->payload())->push([], 503)]);
        app(WeatherForecast::class)->get('arnhem');
        $this->travelTo(CarbonImmutable::parse('2026-11-11 00:01:00', 'Europe/Amsterdam'));
        $this->getJson('/weer/data')->assertStatus(503);
    }
    public function test_seasons_follow_dutch_calendar_boundaries(): void
    {
        foreach ([
            '2026-02-28T22:59:00Z'=>'winter', '2026-02-28T23:00:00Z'=>'spring',
            '2026-05-31T21:59:00Z'=>'spring', '2026-05-31T22:00:00Z'=>'summer',
            '2026-08-31T21:59:00Z'=>'summer', '2026-08-31T22:00:00Z'=>'autumn',
            '2026-11-30T22:59:00Z'=>'autumn', '2026-11-30T23:00:00Z'=>'winter',
            '2027-01-01T00:00:00Z'=>'winter',
        ] as $date=>$expected) {
            $this->assertSame($expected, \App\Support\WeatherSeason::current(new \DateTimeImmutable($date)));
        }
    }

    public function test_local_season_previews_and_images_are_available(): void
    {
        foreach (['spring','summer','autumn','winter'] as $season) {
            $this->get('/weer?season='.$season)->assertOk()->assertSee('data-season="'.$season.'"', false)
                ->assertSee('/images/weather/'.$season.'-nature.png', false)->assertSee('Zeven keer kans op licht!');
            $this->assertFileExists(public_path('images/weather/'.$season.'-nature.png'));
            $this->assertFileExists(public_path('images/weather/'.$season.'-art.png'));
        }
        $this->get('/weer?season[]=summer')->assertOk()->assertSee('data-season="autumn"', false);
        $this->get('/weer?season=unknown')->assertOk()->assertSee('data-season="autumn"', false);
    }

    public function test_follow_dialog_uses_only_configured_profile_urls(): void
    {
        config(['social.profiles'=>[
            'instagram'=>['name'=>'Instagram','url'=>'https://www.instagram.com/example/'],
            'facebook'=>['name'=>'Facebook','url'=>null],
            'tiktok'=>['name'=>'TikTok','url'=>'javascript:alert(1)'],
        ]]);
        $html=view('shared.follow-us')->render();
        $this->assertStringContainsString('href="https://www.instagram.com/example/"', $html);
        $this->assertStringContainsString('Binnenkort', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringNotContainsString('href="https://www.facebook.com', $html);
    }

}
