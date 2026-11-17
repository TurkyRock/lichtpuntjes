<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareSeo extends Command
{
 protected $signature='seo:prepare';
 protected $description='Activeer dynamische robots.txt en voeg veilige statische caching toe met behoud van bestaande serverregels.';
 public function handle():int {
  $directory=storage_path('app/seo-backups');File::ensureDirectoryExists($directory);
  $stamp=now()->format('Ymd-His').'-'.bin2hex(random_bytes(3));
  if(File::exists(public_path('robots.txt'))){
   if(!File::move(public_path('robots.txt'),$directory.'/robots-'.$stamp.'.txt')){$this->error('Statische robots.txt kon niet veilig worden bewaard.');return self::FAILURE;}
   $this->info('Oude statische robots.txt veilig bewaard. De dynamische versie is actief.');
  }
  $file=public_path('.htaccess');$snippet=base_path('maintenance/seo-static-assets.apache');
  if(File::exists($file)&&File::exists($snippet)){
   $begin='# BEGIN LICHTPUNTJES SEO ASSETS';$end='# END LICHTPUNTJES SEO ASSETS';
   $current=File::get($file);$block=$begin."\n".trim(File::get($snippet))."\n".$end;
   if(str_contains($current,$begin)&&!str_contains($current,$end)){$this->error('Onvolledig bestaand SEO-cacheblok. Serverregels niet aangepast.');return self::FAILURE;}
   $updated=str_contains($current,$begin)?preg_replace('/'.preg_quote($begin,'/').'.*?'.preg_quote($end,'/').'/s',$block,$current):rtrim($current)."\n\n".$block."\n";
   if($updated!==$current){
    if(File::put($directory.'/htaccess-'.$stamp.'.txt',$current)===false){$this->error('Backup serverregels mislukt; niets gewijzigd.');return self::FAILURE;}
    File::replace($file,$updated);$this->info('Statische caching toegevoegd; bestaande serverregels bewaard.');
   }else $this->info('Statische caching stond al ingesteld.');
  }else $this->warn('Geen Apache-configuratie of cachefragment gevonden; controleer statische caching met de hostingbeheerder.');
  $this->info('SEO-adressen worden opgebouwd met APP_URL: '.config('app.url'));
  $this->info('Controleer /sitemap.xml en /robots.txt. Voer daarna seo:check uit.');
  return self::SUCCESS;
 }
}
