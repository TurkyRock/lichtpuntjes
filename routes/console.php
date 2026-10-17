<?php
use Illuminate\Support\Facades\Schedule;
if(config('news_cycle.enabled')) {
 Schedule::command('news:cycle')->cron('0 7,10,13,16,19 * * *')->timezone('Europe/Amsterdam')->withoutOverlapping(180);
 // Recover old failed/released illustration jobs shortly after the provider's UTC reset.
 Schedule::command('lichtpuntjes:hervat --limit=15')->dailyAt('00:05')->timezone('UTC')->withoutOverlapping(30);
}

// Refresh the editorial edition at 07:00, independently of provider quotas.
Schedule::command('news:daily')->dailyAt('07:00')->timezone('Europe/Amsterdam')->withoutOverlapping(10);
