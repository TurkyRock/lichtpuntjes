<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\Maintenance\StorageUsage;
class StorageStatus extends Command {
 protected $signature='site:storage';protected $description='Meet websitebestanden en database en controleer de opslaglimiet';
 public function handle(StorageUsage $usage):int {$r=$usage->report(true);$this->line(json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));return self::SUCCESS;}
}
