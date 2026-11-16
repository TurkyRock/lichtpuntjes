<?php
use Illuminate\Support\Facades\Schedule;
if(config('news_hourly.enabled')) {
 Schedule::exec(\Illuminate\Console\Application::phpBinary().' -d memory_limit=512M '.\Illuminate\Console\Application::artisanBinary().' news:hourly --per-category='.(int)config('news_hourly.per_category',3))->hourly()->timezone('Europe/Amsterdam')->withoutOverlapping(70)->appendOutputTo(storage_path('logs/news-hourly.log'));
}
if(config('news_cycle.enabled')) {
 if(!config('news_hourly.enabled')) Schedule::command('news:cycle')->cron('0 7,10,13,16,19 * * *')->timezone('Europe/Amsterdam')->withoutOverlapping(180);
 // Recover old failed/released illustration jobs shortly after the provider's UTC reset.
 Schedule::command('lichtpuntjes:hervat --limit=15')->dailyAt('00:05')->timezone('UTC')->withoutOverlapping(30);
}

// Refresh the editorial edition at 07:00, independently of provider quotas.
Schedule::command('news:daily')->dailyAt('07:00')->timezone('Europe/Amsterdam')->withoutOverlapping(10);

\Illuminate\Support\Facades\Artisan::command('profiles:cleanup',function(){
 $count=0;
 \App\Models\ProfilePhoto::whereIn('status',['pending','doubt','superseded','removed'])->where('updated_at','<',now()->subDays(30))->chunkById(100,function($photos)use(&$count){foreach($photos as $photo){$path=null;\Illuminate\Support\Facades\DB::transaction(function()use($photo,&$path){$user=\App\Models\User::whereKey($photo->user_id)->lockForUpdate()->first();$p=\App\Models\ProfilePhoto::whereKey($photo->id)->lockForUpdate()->first();if(!$p||!in_array($p->status,['pending','doubt','superseded','removed'],true)||$p->updated_at>=now()->subDays(30))return;if($user?->profile_photo_id===$p->id)return;$path=$p->path;$p->update(['path'=>null,'status'=>'removed']);});if($path){\Illuminate\Support\Facades\Storage::disk('local')->delete($path);$count++;}}});
 $this->info($count.' oude privéfotobestanden opgeruimd.');
})->purpose('Ruim verlopen niet-openbare profielfoto’s op');
\Illuminate\Support\Facades\Schedule::command('profiles:cleanup')->dailyAt('03:15')->withoutOverlapping();

// Local AI is asynchronous: resume only unfinished rounds, never create a new round between hours.
if(config('news_hourly.enabled')&&config('local-ai.enabled')) {
 Schedule::command('news:hourly --resume --per-category='.(int)config('news_hourly.per_category',3))
  ->everyMinute()->withoutOverlapping(5)->runInBackground()->appendOutputTo(storage_path('logs/news-hourly.log'));
}

// Also resumes quota-paused text checks, even when the laptop is offline.
Schedule::command('laptop:tick --limit=5')->everyMinute()->withoutOverlapping(10);

// Resume an explicitly started one-off news batch; never start a new round here.
Schedule::command('news:laptop-batch --resume')->everyMinute()->withoutOverlapping(5);

// Repair missing feature scores before the 07:00 edition and throughout the day.
Schedule::command('news:spotlight-score --limit=5')->hourlyAt(40)->timezone('Europe/Amsterdam')
 ->withoutOverlapping(120)->runInBackground()->appendOutputTo(storage_path('logs/spotlight-recovery.log'));

// Retry service failures only; genuine moderation doubts stay with the editor.
Schedule::command('comments:review --limit=10 --retry-errors')->everyTenMinutes()
 ->withoutOverlapping(30)->runInBackground()->appendOutputTo(storage_path('logs/comments-recovery.log'));

// Recover missed payment webhooks only when payments are configured and enabled.
Schedule::command('support:sync --limit=25')->everyFifteenMinutes()
 ->when(fn()=>app(\App\Services\SupportPayments::class)->ready())
 ->withoutOverlapping(30)->runInBackground()->appendOutputTo(storage_path('logs/support-recovery.log'));

Schedule::command('site:storage')->everyFifteenMinutes()->withoutOverlapping(15)->runInBackground()->appendOutputTo(storage_path('logs/storage-monitor.log'));
Schedule::command('site:cleanup --apply')->dailyAt('03:30')->timezone('Europe/Amsterdam')->withoutOverlapping(60)->runInBackground()->appendOutputTo(storage_path('logs/retention.log'));
Schedule::command('site:backup')->dailyAt('04:30')->timezone('Europe/Amsterdam')->withoutOverlapping(120)->runInBackground()->appendOutputTo(storage_path('logs/backup.log'));

Schedule::command('social:prepare')->everyMinute()->withoutOverlapping(5)->runInBackground()->appendOutputTo(storage_path('logs/social-planner.log'));

// Bounded recovery and expiry of encrypted local text tasks.
Schedule::command('local-ai:tick')->everyMinute()->withoutOverlapping(5);
\Illuminate\Support\Facades\Artisan::command('local-ai:token',function(){
 $token=bin2hex(random_bytes(32));$this->line('Laptop worker.json / local_ai_token: '.$token);$this->line('Server .env: LOCAL_AI_TOKEN_HASH='.hash('sha256',$token));$this->warn('Bewaar de sleutel privé; deel hem niet via chat of git.');
})->purpose('Maak een afzonderlijke sleutel voor de lokale tekstworker');

Schedule::command('privacy:cleanup --apply')->dailyAt('03:40')->withoutOverlapping();
