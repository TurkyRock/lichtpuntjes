<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Article;
use Illuminate\Support\Str;
class CookiePrivacyTest extends TestCase {
 use RefreshDatabase;
 private function article():Article {return Article::create(['fingerprint'=>hash('sha256','cookies'),'source_title'=>'Happy','source_name'=>'Test','source_url'=>'https://example.com','status'=>'published','category'=>'media','published_at'=>now(),'nl'=>['title'=>'Goed nieuws'],'en'=>['title'=>'Good news']]);}
 public function test_no_read_tracking_without_valid_consent():void {
  $a=$this->article();
  $this->get('/news/feed')->assertOk()->assertCookieMissing('lichtpunt_visitor');
  $this->postJson('/news/'.$a->id.'/read')->assertOk()->assertJsonPath('counted',false)->assertCookieMissing('lichtpunt_visitor');
  $this->assertDatabaseCount('article_reads',0);
  $this->withCredentials()->withCookie('lichtpunt_consent','v2|yes|'.now()->subDays(181)->timestamp)->postJson('/news/'.$a->id.'/read')->assertJsonPath('counted',false);
  $this->withCredentials()->withCookie('lichtpunt_consent','v0|yes|'.now()->timestamp)->postJson('/news/'.$a->id.'/read')->assertJsonPath('counted',false);
  $this->assertDatabaseCount('article_reads',0);
 }
 public function test_opt_in_and_withdrawal():void {
  $a=$this->article();
  $this->postJson('/cookies/keuze',['statistics'=>true])->assertOk()->assertCookie('lichtpunt_consent');
  $this->withCredentials()->withCookie('lichtpunt_consent','v2|yes|'.now()->timestamp)->postJson('/news/'.$a->id.'/read')->assertOk()->assertJsonPath('views_count',1)->assertCookie('lichtpunt_visitor');
  $this->assertDatabaseCount('article_reads',1);
  $this->postJson('/cookies/keuze',['statistics'=>false])->assertOk()->assertCookieExpired('lichtpunt_visitor');
  $this->withCredentials()->withCookie('lichtpunt_consent','v2|no|'.now()->timestamp)->withCookie('lichtpunt_visitor',(string)Str::uuid())->postJson('/news/'.$a->id.'/read')->assertJsonPath('counted',false)->assertCookieExpired('lichtpunt_visitor');
  $this->assertDatabaseCount('article_reads',1);
 }
 public function test_page_and_contact():void {
  $this->get('/privacy')->assertOk()->assertSee('Two Steps Ahead')->assertSee('Erik Turkesteen')->assertDontSee('info@twostepsahead.nl')->assertSee('privacyContact');
  $this->get('/')->assertOk()->assertSee('lpCookiePanel')->assertSee('data-cookie-settings',false);
  $this->postJson('/cookies/keuze',['statistics'=>'invalid'])->assertUnprocessable();
 }
 public function test_previous_permission_is_not_reused_for_google_analytics():void {
  $a=$this->article();
  $this->withCredentials()->withCookie('lichtpunt_consent','v1|yes|'.now()->timestamp)
   ->postJson('/news/'.$a->id.'/read')->assertOk()->assertJsonPath('counted',false);
  $this->get('/')->assertOk()->assertDontSee('aria-labelledby="lpCookieTitle"  hidden',false);
  $this->assertDatabaseCount('article_reads',0);
  $this->postJson('/cookies/keuze',['statistics'=>true])->assertCookie('lichtpunt_consent','v2|yes|'.now()->timestamp);
 }
 public function test_analytics_information_and_two_clear_choices_are_present():void {
  $this->get('/privacy')->assertOk()->assertSee('Google Analytics 4')->assertSee('alleen op de homepage')->assertSee('_ga');
  $this->get('/')->assertOk()->assertSee('data-cookie-choice="no"',false)->assertSee('data-cookie-choice="yes"',false)->assertSee('Sta bezoekersstatistieken toe')->assertSee('Alleen noodzakelijk');
 }
}
