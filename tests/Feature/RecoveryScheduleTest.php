<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Console\Scheduling\Schedule;
use App\Models\Article;
use App\Services\{AiAvailability,AiWaitException,DailyStoryScore,GeminiJson};
use Carbon\CarbonImmutable;

class RecoveryScheduleTest extends TestCase {
 use RefreshDatabase;
 private function article():Article {
  return Article::create(['fingerprint'=>bin2hex(random_bytes(16)),'origin'=>'news','status'=>'published','source_title'=>'Goed nieuws','source_name'=>'Testbron','source_url'=>'https://example.com/news','category'=>'nature','published_at'=>now(),'nl'=>['title'=>'Goed nieuws','body'=>['Een positief verhaal.']],'en'=>['body'=>['Good news.']]]);
 }
 public function test_known_ai_pause_does_not_spend_a_score_attempt():void {
  $a=$this->article();app(AiAvailability::class)->pause(429,[]);
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldNotReceive('ask'));
  try {app(DailyStoryScore::class)->assess($a);$this->fail('Expected pause');}catch(AiWaitException $e){}
  $this->assertSame(0,(int)$a->fresh()->daily_score_attempts);
 }
 public function test_new_ai_pause_restores_the_attempt_and_retains_missing_score():void {
  $a=$this->article();
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->once()->andThrow(new AiWaitException(CarbonImmutable::now()->addMinutes(2),'rate_limit')));
  try {app(DailyStoryScore::class)->assess($a);$this->fail('Expected pause');}catch(AiWaitException $e){}
  $this->assertSame(0,(int)$a->fresh()->daily_score_attempts);
  $this->assertNull($a->fresh()->daily_scored_at);
 }
 public function test_successful_score_is_saved():void {
  $a=$this->article();
  $this->mock(GeminiJson::class,fn($m)=>$m->shouldReceive('ask')->once()->andReturn(['daily_score'=>85,'daily_reason_nl'=>'Hoopvol','daily_reason_en'=>'Hopeful']));
  app(DailyStoryScore::class)->assess($a);
  $this->assertSame(85,$a->fresh()->daily_score);
  $this->assertNotNull($a->fresh()->daily_scored_at);
 }
 public function test_recovery_tasks_are_registered_with_overlap_protection():void {
  $events=collect(app(Schedule::class)->events());
  foreach(['news:spotlight-score --limit=5'=>'40 * * * *','comments:review --limit=10 --retry-errors'=>'*/10 * * * *','support:sync --limit=25'=>'*/15 * * * *'] as $command=>$expression){
   $event=$events->first(fn($e)=>str_contains($e->command??'',$command));
   $this->assertNotNull($event,$command);$this->assertSame($expression,$event->expression);
   $this->assertTrue($event->withoutOverlapping);$this->assertTrue($event->runInBackground);
  }
  $payment=$events->first(fn($e)=>str_contains($e->command??'','support:sync'));
  config(['support.enabled'=>false]);$this->assertFalse($payment->filtersPass($this->app));
 }
}
