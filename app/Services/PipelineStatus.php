<?php
namespace App\Services;

use App\Models\{Article,LocalAiJob,LaptopImageJob};
use Illuminate\Support\Facades\{Cache,File};

final class PipelineStatus
{
    private function queue(string $model,bool $text): array
    {
        $query=$model::query();
        if($text)$query->where('expires_at','>',now());
        $counts=(clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status');
        $ready=(clone $query)->where('status','queued')->where(fn($q)=>$q->whereNull('available_at')->orWhere('available_at','<=',now()));
        $oldest=(clone $ready)->min('available_at')??(clone $ready)->min('created_at');
        $generated=(clone $query)->where('status','generated')->min('updated_at');
        return ['queued'=>(int)($counts['queued']??0),'ready'=>(clone $ready)->count(),
            'processing'=>(int)($counts['processing']??0),'generated'=>(int)($counts['generated']??0),
            'completed'=>(int)($counts['completed']??0)+(int)($counts['applied']??0),
            'failed'=>(int)($counts['failed']??0),
            'expired_leases'=>(clone $query)->where('status','processing')->where('lease_until','<=',now())->count(),
            'oldest_ready_minutes'=>$oldest?max(0,(int)\Carbon\CarbonImmutable::parse($oldest)->diffInMinutes(now(),false)):0,
            'oldest_generated_minutes'=>$generated?max(0,(int)\Carbon\CarbonImmutable::parse($generated)->diffInMinutes(now(),false)):0];
    }

    public function snapshot(): array
    {
        $text=$this->queue(LocalAiJob::class,true);$images=$this->queue(LaptopImageJob::class,false);$alerts=[];
        $last=Cache::get('lichtpunt-pipeline-scheduler-seen');
        $minutes=is_int($last)?max(0,(int)floor((now()->timestamp-$last)/60)):null;
        if($minutes!==null&&$minutes>=5)$alerts[]='ACTIE: minuutplanner is '.$minutes.' minuten niet gezien. Controleer Plesk Geplande taken; schedule:run moet elke minuut draaien.';
        if($minutes===null)$alerts[]='LET OP: minuutplanner nog niet bevestigd. Voer schedule:run uit en controleer de Plesk-planning.';
        foreach(['Tekst'=>$text,'Beeld'=>$images] as $name=>$q){
            if($q['failed'])$alerts[]='ACTIE: '.$name.' heeft '.$q['failed'].' mislukte opdracht(en). '.($name==='Tekst'?'Bekijk local-ai:status; herhaal niet voortdurend mislukte opdrachten.':'Bekijk news:image-status en de laptopmeldingen.');
            if($q['expired_leases'])$alerts[]='LET OP: '.$name.' heeft '.$q['expired_leases'].' verlopen verwerking(en). Controleer of de laptopworker nog verbinding heeft; opdrachten kunnen opnieuw worden opgehaald.';
            $active=$text['processing']+$images['processing']-$text['expired_leases']-$images['expired_leases'];
            if($q['ready']&&$active===0&&$q['oldest_ready_minutes']>=15)$alerts[]='ACTIE: '.$name.' wacht al '.$q['oldest_ready_minutes'].' minuten zonder actieve verwerking. Controleer de laptopworker, verbinding en ComfyUI; verwijder de wachtrij niet.';
        }
        if($images['generated']&&$images['oldest_generated_minutes']>=10)$alerts[]='LET OP: '.$images['generated'].' beeld(en) wachten op controle; oudste '.$images['oldest_generated_minutes'].' minuten. Controleer minuutplanner en lokale tekst-/beeldcontrole.';
        if (config('local-ai.enabled')) {
            $recent=Article::query()->where('origin','news')->where('updated_at','>=',now()->subDay());
            $rejected=(clone $recent)->where('status','rejected')->where('reason','like',NewsImporter::FINAL_EDIT_REJECTION_PREFIX.'%');
            $rejectedCount=$rejected->count();
            if ($rejectedCount) {
                $ids=$rejected->latest('updated_at')->latest('id')->limit(5)->pluck('id')->map(fn($id)=>'#'.$id)->implode(', ');
                $alerts[]='LET OP: tweede taalcontrole: '.$rejectedCount.' artikel(en) afgekeurd in de afgelopen 24 uur. Laatste: '.$ids.'.';
            }
            $failed=$recent->where('status','error')->where('reason',NewsImporter::FINAL_EDIT_ERROR)->count();
            if ($failed) {
                $alerts[]='ACTIE: tweede taalcontrole: '.$failed.' technische mislukking(en) in de afgelopen 24 uur.';
            }
        }
        $retry=null;$path=(string)config('laptop-images.news_batch_file',storage_path('app/laptop-news-batch.json'));
        if(File::exists($path)){
            try{$batch=json_decode(File::get($path),true,512,JSON_THROW_ON_ERROR);if(!($batch['finished']??false)&&!empty($batch['retry_at']))$retry=\Carbon\CarbonImmutable::parse($batch['retry_at'])->timezone('Europe/Amsterdam')->format('d-m H:i:s');}
            catch(\Throwable $e){$alerts[]='ACTIE: batchvoortgang kon niet worden gelezen. Bewaar het bestand en controleer het serverlogboek.';}
        }
        return ['text'=>$text,'images'=>$images,'scheduler_minutes_ago'=>$minutes,'batch_retry_at'=>$retry,
            'news_import_open'=>\App\Support\NewsImportWindow::isOpen(),'alerts'=>$alerts];
    }
}
