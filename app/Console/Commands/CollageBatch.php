<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class CollageBatch extends Command
{
    protected $signature='news:collage-batch {--pages=50 : Maximum extra bronpaginas deze run} {--restart-feed : Herstart de bronpaginering, behoud voorbereide berichten en beelden}';
    protected $description='Vervang de openbare nieuwscollectie door 30 nieuwe artikelen met knipselcollages';
    public function handle():int
    {
        return $this->call('news:replace',['--count'=>30,'--collage'=>true,'--pages'=>$this->option('pages'),'--restart-feed'=>(bool)$this->option('restart-feed')]);
    }
}
