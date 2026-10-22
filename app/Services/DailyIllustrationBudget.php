<?php
namespace App\Services;

use App\Models\{Article,LightspotSubmission};
use Illuminate\Support\Facades\{Cache,File};

class DailyIllustrationBudget
{
    public function reserve(Article $article): void
    {
        if (!config('news_cycle.image_budget_enabled')) return;
        if (config('news_images.provider')==='cloudflare' && Cache::get('lichtpunt-cloudflare-exhausted')) {
            throw new ImageBudgetException('AI-puntjes: het gratis Cloudflare-tegoed is vandaag op. Je goedgekeurde verhaal wacht op nieuw tegoed.');
        }
        $kind=$article->origin==='community'?'community':'news';
        if ($kind==='community') {
            $first=LightspotSubmission::where('status','image_wait')->whereHas('user',fn($q)=>$q
                ->where('is_suspended',false)->whereNotNull('email_verified_at')->whereNotNull('community_accepted_at'))
                ->oldest('id')->first();
            if ($first && $first->article_id!==$article->id) {
                throw new NewsServiceException('Er staat een eerder goedgekeurd verhaal voor je in de illustratiewachtrij.');
            }
        }
        $lock=Cache::lock('lichtpunt-daily-image-budget',30);
        if (!$lock->get()) throw new NewsServiceException('De illustratiedagteller wordt bijgewerkt. Probeer later opnieuw.');
        try {
            $path=(string) config('news_cycle.budget_file');
            $day=now('UTC')->toDateString();$state=['day'=>$day,'news'=>0,'community'=>0];
            if (is_file($path)) {
                try {$saved=json_decode(File::get($path),true,512,JSON_THROW_ON_ERROR);} catch (\Throwable $e) {
                    throw new NewsServiceException('Illustratiedagteller is beschadigd. Geen generatie gestart.');
                }
                if (!is_array($saved)||!is_string($saved['day']??null)||!is_int($saved['news']??null)||!is_int($saved['community']??null)||$saved['news']<0||$saved['community']<0) {
                    throw new NewsServiceException('Illustratiedagteller is ongeldig. Geen generatie gestart.');
                }
                if($saved['day']===$day)$state=$saved;
            }
            $max=(int) config('news_cycle.'.$kind.'_images');
            if ($state[$kind]>=$max) throw new ImageBudgetException('AI-puntjes: de dagruimte voor '.($kind==='community'?'gebruikersverhalen':'nieuwsillustraties').' is op. We hervatten bij nieuw dagtegoed.');
            // Failed generations also count. This deliberately favours staying within free usage.
            $state[$kind]++;
            File::ensureDirectoryExists(dirname($path));$tmp=$path.'.tmp';
            if(File::put($tmp,json_encode($state,JSON_THROW_ON_ERROR))===false||!rename($tmp,$path))throw new NewsServiceException('Illustratiedagteller opslaan mislukt. Geen generatie gestart.');
        } finally {$lock->release();}
    }

    public function exhausted(): void
    {
        Cache::put('lichtpunt-cloudflare-exhausted',true,now('UTC')->addDay()->startOfDay()->addMinute());
    }
}
