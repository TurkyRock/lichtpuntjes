<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class PhotoTrial extends Command
{
 protected $signature='news:photo-trial {--pages=30 : Maximaal extra nieuwspaginas deze run, 1 tot 50} {--restart-feed : Vernieuw bronpaginering, behoud voorbereide berichten en beelden}';
 protected $description='Bereid 30 nieuwe berichten met fotorealistische AI-sfeerbeelden voor en wissel pas als alles klaar is';

 public function handle():int {
  return $this->call('news:replace',['--count'=>30,'--photorealistic'=>true,'--pages'=>$this->option('pages'),'--restart-feed'=>(bool)$this->option('restart-feed')]);
 }
}
