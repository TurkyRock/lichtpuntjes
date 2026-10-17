<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
class PrepareSeo extends Command {
 protected $signature='seo:prepare';
 protected $description='Activeer dynamische robots.txt en controleer het publieke websiteadres';
 public function handle():int {
  if(File::exists(public_path('robots.txt'))){$directory=storage_path('app/seo-backups');File::ensureDirectoryExists($directory);File::move(public_path('robots.txt'),$directory.'/robots-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.txt');$this->info('Oude statische robots.txt veilig bewaard. De dynamische versie is actief.');}
  $this->info('SEO-adressen worden opgebouwd met APP_URL: '.config('app.url'));
  $this->info('Controleer /sitemap.xml en /robots.txt. Stel vóór publicatie APP_URL in op het definitieve HTTPS-domein.');
  return self::SUCCESS;
 }
}
