<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class PipelineStatus extends Command
{
    protected $signature='pipeline:status';
    protected $description='Toon wachtrijen en mogelijke blokkades zonder teksten of sleutels';
    public function handle(): int
    {
        $status=app(\App\Services\PipelineStatus::class)->snapshot();
        $this->table(['Wachtrij','Wachtend','Bezig','Klaar voor controle','Klaar','Mislukt','Oudste beschikbare taak (min)'],
            collect(['Tekst'=>'text','Beeld'=>'images'])->map(fn($key,$name)=>[$name,$status[$key]['queued'],$status[$key]['processing'],$status[$key]['generated'],$status[$key]['completed'],$status[$key]['failed'],$status[$key]['oldest_ready_minutes']])->values()->all());
        $this->line('Minuutplanner: '.($status['scheduler_minutes_ago']===null?'nog niet bevestigd':$status['scheduler_minutes_ago'].' minuten geleden gezien').'.');
        $this->line($status['news_import_open']?'Nieuwsimport: binnen dagvenster 06:00–22:00.':'Nieuwsimport: normale nachtrust tot 06:00; beelden kunnen doorgaan.');
        if($status['batch_retry_at'])$this->line('Opgeslagen hervatmoment batch: '.$status['batch_retry_at'].' Nederlandse tijd.');
        foreach($status['alerts'] as $alert)$this->warn($alert);
        $this->line('Dit is een momentopname. Lang wachten is een signaal om te controleren, geen bewijs dat een taak definitief vastzit.');
        $this->line('Regionale bakken en aanvullende bronnen: news:sources --status');
        return self::SUCCESS;
    }
}
