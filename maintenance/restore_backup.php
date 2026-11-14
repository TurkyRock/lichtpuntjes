<?php
// Decrypt to a new archive only. Never overwrite or automatically restore a live database.
if(PHP_SAPI!=='cli')exit(1);
if($argc!==3){fwrite(STDERR,"Gebruik: php restore_backup.php backup.tar.gz.enc nieuw-archief.tar.gz\n");exit(1);}
fwrite(STDOUT,"Afzonderlijke back-upsleutel (base64): ");$key=base64_decode(trim(fgets(STDIN)),true);
if(!$key||strlen($key)!==32||!function_exists('sodium_crypto_secretstream_xchacha20poly1305_init_pull')){fwrite(STDERR,"Ongeldige sleutel of PHP sodium ontbreekt.\n");exit(1);}
$in=@fopen($argv[1],'rb');$out=@fopen($argv[2],'xb');
if(!$in||!$out){if($in)fclose($in);if($out){fclose($out);unlink($argv[2]);}fwrite(STDERR,"Bron onleesbaar of doel bestaat al.\n");exit(1);}
$read=function($size)use($in){$data='';while(strlen($data)<$size&&!feof($in)){$chunk=fread($in,$size-strlen($data));if($chunk===false)throw new RuntimeException('Leesfout.');$data.=$chunk;}if(strlen($data)!==$size)throw new RuntimeException('Onvolledig bestand.');return $data;};
try{
 if($read(10)!=="LPBACKUP1\n")throw new RuntimeException('Onbekend formaat.');
 $state=sodium_crypto_secretstream_xchacha20poly1305_init_pull($read(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES),$key);$final=false;
 while(!feof($in)){$length=fread($in,4);if($length==='')break;if(strlen($length)!==4||$final)throw new RuntimeException('Ongeldig einde.');$size=unpack('N',$length)[1];if($size<17||$size>64*1024*1024)throw new RuntimeException('Ongeldig blok.');$result=sodium_crypto_secretstream_xchacha20poly1305_pull($state,$read($size));if($result===false)throw new RuntimeException('Sleutel of bestandscontrole ongeldig.');[$plain,$tag]=$result;if(fwrite($out,$plain)!==strlen($plain))throw new RuntimeException('Schrijffout.');$final=$tag===SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;}
 if(!$final)throw new RuntimeException('Back-up onvolledig.');
 fclose($in);fclose($out);fwrite(STDOUT,"Archief ontsleuteld. Zet eerst terug in een aparte testomgeving.\n");
}catch(Throwable $e){fclose($in);fclose($out);unlink($argv[2]);fwrite(STDERR,"Ontsleutelen mislukt: ".$e->getMessage()."\n");exit(1);}
