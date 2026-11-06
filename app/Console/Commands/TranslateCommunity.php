<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\{PositiveMoment,Comment};
use App\Services\ContributionTranslator;
class TranslateCommunity extends Command {
    protected $signature='community:translate {--limit=50} {--include-private : Ook eigen bewaarde momentjes met eerdere AI-toestemming}';
    protected $description='Zet ontbrekende Nederlandse/Engelse vertalingen van goedgekeurde bijdragen in de wachtrij';
    public function handle():int {
        $raw=(string)$this->option('limit');if(!ctype_digit($raw)||(int)$raw<1||(int)$raw>500){$this->error('--limit moet tussen 1 en 500 liggen.');return self::FAILURE;}
        $left=(int)$raw;$queued=0;
        foreach(['moment'=>PositiveMoment::class,'comment'=>Comment::class] as $kind=>$class){
            $q=$class::where('status','approved')->where('translation_state','!=','ready')->whereHas('user',fn($q)=>$q->where('is_suspended',false));
            if($kind==='moment')$q->where(fn($q)=>$q->where('visibility','public')->when($this->option('include-private'),fn($q)=>$q->orWhere(fn($q)=>$q->where('visibility','private')->whereNotNull('ai_accepted_at'))));
            foreach($q->orderBy('id')->limit($left)->get() as $model){ContributionTranslator::queue($kind,$model->id);$queued++;$left--;}
            if($left<1)break;
        }
        $this->info($queued.' bijdragen klaargezet voor vertaling. Start je bestaande queueworker om ze te verwerken.');return self::SUCCESS;
    }
}
