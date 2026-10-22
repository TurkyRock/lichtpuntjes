<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$files=json_decode(<<<'FILES'
{
  "public/js/lightpoint-notice.js": "d42b3852c50e4a0f9d1e6f66399af6f1293949f79ffdd73f8c0ef5bc39dc808f",
  "app/Services/LichtpuntIllustrationPrompt.php": "3c2301a836d2868cac7baa08e06eef4fbfa11f009eaf58b32641d000575ef43a",
  "app/Console/Commands/GenerateLichtpuntIllustrations.php": "b66cccaf1a54394dbf7cf129286f3bea7255eaefe5f3dba1fadd314492a48bd9"
}
FILES,true);
$ok=true;foreach($files as $file=>$hash){if(!is_file(__DIR__.'/'.$file)||hash_file('sha256',__DIR__.'/'.$file)!==$hash){echo 'Bestand ontbreekt of wijkt af: '.$file.PHP_EOL;$ok=false;}}echo $ok?'Fotostijl en melding volledig geplaatst.'.PHP_EOL:'Plaats de bestanden opnieuw in de juiste projectmap.'.PHP_EOL;exit($ok?0:1);
