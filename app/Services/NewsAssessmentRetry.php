<?php
namespace App\Services;
use App\Models\Article;
use Illuminate\Support\Sleep;
class NewsAssessmentRetry
{
    /** CLI-only backoff: keep the same article until Gemini recovers or Ctrl+C. */
    public function assess(NewsImporter $importer,Article $article,bool $stage=false,?callable $notice=null):void
    {
        $retry=0;
        while(true){
            try{$importer->assess($article,$stage);return;}
            catch(GeminiTemporaryException $e){
                $seconds=$retry++===0?60:300;
                if($notice)$notice('#'.$article->id.' · Gemini tijdelijk niet beschikbaar. Nieuwe poging over '.$seconds.' seconden (Ctrl+C om te stoppen).');
                // Short chunks make cancellation responsive, including on Windows.
                for($waited=0;$waited<$seconds;$waited+=5)Sleep::for(5)->seconds();
                $article->refresh();
                if($article->hidden_at)return;
            }
        }
    }
}
