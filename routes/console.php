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
