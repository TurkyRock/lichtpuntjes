<?php
namespace App\Console\Commands;
use App\Models\Article;
use App\Services\{ArticlePhotoFinder,LichtpuntIllustrationPrompt};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache,File};
class CollageTrial extends Command {
 protected $signature='news:collage-trial {--restore : Zet uitsluitend de proefbeelden terug naar hun eerdere afbeelding}';
 protected $description='Probeer maximaal drie knipselcollages bij huidige nieuwsartikelen, met hervatten en herstel';
 public function handle(ArticlePhotoFinder $finder):int {
  return Cache::lock('article-collage-trial',7200)->block(2,function()use($finder){
   $path=storage_path('app/article-collage-trial.json');
   $state=is_file($path)?json_decode(File::get($path),true,512,JSON_THROW_ON_ERROR):null;
   if($state!==null&&(!is_array($state)||($state['version']??null)!==1||!is_array($state['articles']??null))){$this->error('Proefbestand ongeldig; er is niets aangepast.');return self::FAILURE;}
   if($this->option('restore')){
    if(!$state){$this->info('Er is geen knipselproef om terug te zetten.');return self::SUCCESS;}
    $restored=0;$conflicts=0;
    foreach($state['articles'] as &$entry){$a=Article::find($entry['id']);if(!$a)continue;$current=$a->photo??[];
     $ours=isset($entry['generated_sha'])&&($current['sha256']??null)===$entry['generated_sha']&&in_array($current['style']??null,[LichtpuntIllustrationPrompt::COLLAGE_STYLE,'lichtpunt-torn-paper-v2','lichtpunt-colour-paper-v3','lichtpunt-warm-paper-v4'],true);
     $waiting=($current['requested_style']??null)===LichtpuntIllustrationPrompt::COLLAGE_STYLE&&array_diff_key($current,['requested_style'=>true])===array_diff_key($entry['before']??[],['requested_style'=>true]);
     if($ours||($entry['state']!=='ready'&&$waiting)){$a->update(['photo'=>$entry['before']]);$entry['state']='restored';$restored++;}elseif($entry['state']!=='restored')$conflicts++;
    }unset($entry);$this->save($path,$state);$this->info($restored.' proefbeelden teruggezet; '.$conflicts.' nieuwere wijzigingen overgeslagen.');return self::SUCCESS;
   }
   if(!$state){$selected=Article::visible()->where('origin','news')->latest('published_at')->latest('id')->limit(100)->get();$unique=$selected->unique('category')->take(3);$ids=$unique->pluck('id');$unique=$unique->concat($selected->reject(fn($a)=>$ids->contains($a->id))->take(3-$unique->count()));
    $state=['version'=>1,'articles'=>$unique->map(fn($a)=>['id'=>$a->id,'before'=>$a->photo,'state'=>'pending'])->values()->all()];$this->save($path,$state);
   }
   $made=0;
   foreach($state['articles'] as &$entry){if(in_array($entry['state'],['ready','restored'],true))continue;$a=Article::visible()->where('origin','news')->find($entry['id']);if(!$a){$entry['state']='skipped';$this->save($path,$state);continue;}
    $this->line('Knipselbeeld bij #'.$a->id.': '.($a->nl['title']??$a->source_title));
    $a->update(['photo'=>array_merge($a->photo??[],['requested_style'=>LichtpuntIllustrationPrompt::COLLAGE_STYLE])]);
    try{$finder->attach($a);$entry['state']='ready';$entry['generated_sha']=$a->fresh()->photo['sha256'];$made++;$this->save($path,$state);}
    catch(\Throwable $e){$this->save($path,$state);$this->warn('De beeldproef wacht. Je huidige afbeelding blijft zichtbaar. Voer dezelfde opdracht later opnieuw uit.');return self::FAILURE;}
   }unset($entry);$this->info($made.' nieuwe knipselbeelden. De proef vervangt maximaal drie bestaande afbeeldingen, geen artikelen.');return self::SUCCESS;
  });
 }
 private function save(string $path,array $state):void {File::ensureDirectoryExists(dirname($path));$tmp=$path.'.tmp';if(File::put($tmp,json_encode($state,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false||!rename($tmp,$path))throw new \RuntimeException('Proefbestand opslaan mislukt.');}
}
