<?php
// Run only from the command line using the NEW project's trusted dependencies.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/vendor/autoload.php';
$old=realpath($argv[1]??'');$new=dirname(__DIR__);
if(!$old||$old===$new||!is_file($old.'/.env')||is_file($new.'/.env')){fwrite(STDERR,"Oude map ongeldig of nieuwe .env bestaat al. Er is niets overschreven.\n");exit(1);}
try {
 $raw=file_get_contents($old.'/.env');$env=Dotenv\Dotenv::parse($raw);
 if(($env['DB_CONNECTION']??'sqlite')!=='sqlite')throw new RuntimeException('Deze veilige overstap is voor SQLite. Exporteer en kloon een MySQL/MariaDB-database apart; verbind de nieuwe versie niet met de originele database tijdens de test.');
 if(!empty($env['DB_URL'])||!empty($env['DATABASE_URL']))throw new RuntimeException('Database-URL ingesteld: eerst de databasebestemming handmatig controleren.');
 if(empty($env['APP_KEY']))throw new RuntimeException('APP_KEY ontbreekt; bestaande sleutel is nodig.');
 $db=$env['DB_DATABASE']??($old.'/database/database.sqlite');
 if(!preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~',$db))$db=$old.'/'.$db;
 if(!is_file($db))throw new RuntimeException('Bestaande SQLite-database niet gevonden. Controleer DB_DATABASE lokaal.');
 $dest=$new.'/database/database.sqlite';if(file_exists($dest))throw new RuntimeException('Doeldatabase bestaat al; stop om overschrijven te voorkomen.');
 $pdo=new PDO('sqlite:'.$db);$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->exec('PRAGMA busy_timeout=10000');
 $pdo->exec('VACUUM INTO '.$pdo->quote($dest));$pdo=null;
 $copy=new PDO('sqlite:'.$dest);if($copy->query('PRAGMA integrity_check')->fetchColumn()!=='ok')throw new RuntimeException('Databasekopie is niet geldig.');$copy=null;
 // Copy only runtime state, not executable old source, sessions or logs.
 foreach(['news-replacement-state.json','cloudflare-illustration-requests.json'] as $name){if(is_file($old.'/storage/app/'.$name))copy($old.'/storage/app/'.$name,$new.'/storage/app/'.$name);}
 foreach(['illustration-backups','news-replacement-backups'] as $dir){
  $from=$old.'/storage/app/'.$dir;if(!is_dir($from))continue;
  foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from,FilesystemIterator::SKIP_DOTS)) as $f){
   if($f->isLink()||!$f->isFile()||strtolower($f->getExtension())!=='json')continue;
   $target=$new.'/storage/app/'.$dir.'/'.substr($f->getPathname(),strlen($from)+1);if(!is_dir(dirname($target)))mkdir(dirname($target),0700,true);copy($f->getPathname(),$target);
  }
 }
 $source=$old.'/public/news-images';$images=0;
 if(is_dir($source))foreach(new DirectoryIterator($source) as $file){
  if(!$file->isFile()||$file->isLink())continue;
  if(!preg_match('/^[a-f0-9]{64}\.(?:jpg|png)$/i',$file->getFilename()))continue;
  $info=@getimagesize($file->getPathname());if(!$info||!in_array($info['mime']??'',['image/jpeg','image/png'],true))continue;
  if(!is_dir($new.'/public/news-images'))mkdir($new.'/public/news-images',0755,true);
  copy($file->getPathname(),$new.'/public/news-images/'.$file->getFilename());$images++;
 }
 $apple=$env['APPLE_PRIVATE_KEY_PATH']??($old.'/storage/app/private/apple/AuthKey.p8');
 if(is_file($apple)){if(!is_dir($new.'/storage/app/private/apple'))mkdir($new.'/storage/app/private/apple',0700,true);copy($apple,$new.'/storage/app/private/apple/AuthKey.p8');}
 $set=['APP_DEBUG'=>'false','DB_DATABASE'=>'"'.str_replace('\\','/',$dest).'"','DB_QUEUE_RETRY_AFTER'=>'2100','SESSION_ENCRYPT'=>'true','LOG_CHANNEL'=>'daily','LOG_LEVEL'=>'warning','LOG_DAILY_DAYS'=>'14'];
 if(is_file($new.'/storage/app/private/apple/AuthKey.p8'))$set['APPLE_PRIVATE_KEY_PATH']='"'.str_replace('\\','/',$new.'/storage/app/private/apple/AuthKey.p8').'"';
 foreach($set as $key=>$value){$raw=preg_replace('/^'.preg_quote($key,'/').'=[^\r\n]*(?:\r?\n|$)/m','',$raw);$raw=rtrim($raw)."\n$key=$value\n";}
 // Remove credentials belonging to the retired provider from the new env only.
 $raw=preg_replace('/^(?:POLLINATIONS_API_KEY|NEWS_POLLINATIONS_IMAGE_LIMIT)=[^\r\n]*(?:\r?\n|$)/m','',$raw);
 if(file_put_contents($new.'/.env',$raw)===false)throw new RuntimeException('Nieuwe .env opslaan mislukt.');chmod($new.'/.env',0600);
 echo "Database gecontroleerd en gekopieerd; $images afbeeldingen overgenomen. Origineel blijft behouden.\n";
}catch(Throwable $e){fwrite(STDERR,"Overstap gestopt. Controleer databasepad, vrije ruimte en schrijfrechten. ".($e instanceof RuntimeException&&!($e instanceof PDOException)?$e->getMessage():'Geen geheimen weergegeven.')."\n");exit(1);}
