<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Cache,DB,File};
use Symfony\Component\Process\Process;
class BackupSite extends Command {
 protected $signature='site:backup';protected $description='Versleutel een siteback-up en bewaar deze via rclone buiten de server';
 private function command(array $args,int $timeout=1800):string {$p=new Process($args);$p->setTimeout($timeout);$p->run();if(!$p->isSuccessful())throw new \RuntimeException('Extern back-upcommando mislukt. Controleer rclone, tar en databasehulpmiddelen.');return $p->getOutput();}
 protected function archiveArguments(string $dir,string $driver):array {return ['tar','-czf','-','--exclude=storage/app/backup-tmp-*','--exclude=storage/logs','--exclude=vendor','--exclude=node_modules','--exclude=.git','-C',base_path(),'.','-C',$dir,$driver==='sqlite'?'database.sqlite':'database.sql'];}
 public function handle():int {
  $remote=trim((string)config('retention.backup_remote'));$key=base64_decode((string)config('retention.backup_key'),true);
  if(!$remote||!$key){$this->warn('Externe back-up nog niet ingesteld. Zie /beheer/opslag.');return self::SUCCESS;}
  if(!preg_match('~^[A-Za-z0-9_-]+:[A-Za-z0-9_./-]*$~D',$remote)||str_contains($remote,'..')||strlen($key)!==32||!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_push')){$this->error('Gebruik een rclone-remote en een base64-sleutel van 32 bytes.');return self::FAILURE;}
  $lock=Cache::lock('site-offsite-backup',7200);if(!$lock->get())return self::SUCCESS;
  $dir=storage_path('app/backup-tmp-'.bin2hex(random_bytes(12)));$ok=false;
  try {
   if(!app(\App\Services\Maintenance\StorageUsage::class)->importsAllowed())throw new \RuntimeException('Te weinig ruimte voor een tijdelijke back-up.');
   $usage=app(\App\Services\Maintenance\StorageUsage::class)->report();if(($usage['quota_bytes']-$usage['used_bytes'])<max(50*1024*1024,$usage['database_bytes']*3))throw new \RuntimeException('Te weinig ruimte voor een databasesnapshot.');
   File::makeDirectory($dir,0700,true);$sql=$dir.'/database.sql';$connection=DB::connection();$driver=$connection->getDriverName();
   if($driver==='sqlite'){
    // A live SQLite snapshot through VACUUM INTO includes WAL data consistently.
    $connection->statement('VACUUM INTO '.$connection->getPdo()->quote($dir.'/database.sqlite'));
   }elseif($driver==='mysql'){
    $cfg=$connection->getConfig();$escape=fn($v)=>'"'.str_replace(["\\","\n","\r",'"'],['\\\\','\\n','\\r','\\"'],(string)$v).'"';
    $auth=$dir.'/mysql.cnf';File::put($auth,"[client]\nuser=".$escape($cfg['username'])."\npassword=".$escape($cfg['password'])."\nhost=".$escape($cfg['host'])."\nport=".(int)($cfg['port']??3306)."\n".(empty($cfg['unix_socket'])?'':"socket=".$escape($cfg['unix_socket'])."\n"));chmod($auth,0600);
    $this->command(['mysqldump','--defaults-extra-file='.$auth,'--single-transaction','--quick','--skip-lock-tables','--no-tablespaces','--result-file='.$sql,'--',(string)$cfg['database']]);File::delete($auth);
   }else throw new \RuntimeException('Dit databasetype wordt nog niet ondersteund.');
   $name='lichtpuntjes-'.now('UTC')->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.tar.gz.enc';
   $destination=rtrim($remote,'/').'/lichtpuntjes-backups/';$sent=0;
   // Stream compressed encrypted blocks straight off-site: no large local archive.
   [$state,$header]=sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
   $tar=new Process($this->archiveArguments($dir,$driver));$tar->setTimeout(1800);$tar->start();
   $blocks=(function()use($tar,&$state,$header,&$sent){
    $first="LPBACKUP1\n".$header;$sent+=strlen($first);yield $first;
    foreach($tar->getIterator() as $type=>$chunk){if($type!==Process::OUT)continue;
     $block=sodium_crypto_secretstream_xchacha20poly1305_push($state,$chunk);$frame=pack('N',strlen($block)).$block;$sent+=strlen($frame);yield $frame;
    }
    $tar->wait();if(!$tar->isSuccessful())throw new \RuntimeException('Back-up archiveren mislukt.');
    $block=sodium_crypto_secretstream_xchacha20poly1305_push($state,'','',SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL);$frame=pack('N',strlen($block)).$block;$sent+=strlen($frame);yield $frame;
   })();
   $upload=new Process(['rclone','rcat',$destination.$name]);$upload->setTimeout(1800);$upload->setInput($blocks);
   try{$upload->run();if(!$upload->isSuccessful())throw new \RuntimeException('Externe overdracht mislukt.');}
   finally{if($tar->isRunning())$tar->stop();if($upload->isRunning())$upload->stop();}
   $listing=json_decode($this->command(['rclone','lsjson',$destination,'--files-only']),true,512,JSON_THROW_ON_ERROR);
   $found=false;foreach($listing as $entry)if(($entry['Name']??'')===$name&&(int)($entry['Size']??-1)===$sent)$found=true;
   if(!$found)throw new \RuntimeException('Externe ontvangst is niet bevestigd.');
   foreach($listing as $entry){$old=$entry['Name']??'';if(preg_match('/^lichtpuntjes-\d{8}-\d{6}-[a-f0-9]{8}\.tar\.gz\.enc$/D',$old)&&\Carbon\Carbon::parse($entry['ModTime'])->lt(now()->subDays(config('retention.backup_days'))))$this->command(['rclone','deletefile',$destination.$old]);}
   $ok=true;$this->info('Versleutelde back-up extern opgeslagen.');return self::SUCCESS;
  }catch(\Throwable $e){if(isset($destination,$name)){try{$this->command(['rclone','deletefile',$destination.$name],30);}catch(\Throwable $ignored){}}$this->error('Externe back-up mislukt. Controleer configuratie, vrije ruimte en hulpmiddelen.');return self::FAILURE;}
  finally{if(is_dir($dir))File::deleteDirectory($dir);Cache::forever('site-backup-last',['at'=>now()->toIso8601String(),'ok'=>$ok]);$lock->release();}
 }
}
