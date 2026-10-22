<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$files=json_decode(<<<'FILES'
{
  "public/css/home-daily.css": "e5495f015e8ce5254959a5e4d199688ac13183fb0fe29ccfa1c25c422ba67dcf",
  "public/css/profile-photos.css": "0c0b7bce23f9a6969ffe1d71090af09781952b1e3179df8046a1d843b57a76f5",
  "resources/views/lichtpunt.blade.php": "7c56d7b8f1013a7231a314ba8f01e78a2a22961eca41085341729a55ee0d4411",
  "resources/views/seo/cards.blade.php": "554afa530b82cd3dab1ef924f37960812f9afcaae3c908c58722befd9e4684f8",
  "resources/views/homepage/highlights.blade.php": "d6e0be40055624096418401290a84875ea8a48a84ce08cc09752a7c2415d0b36",
  "resources/views/homepage/moments.blade.php": "728a450dbf2eead773b77fc687f71f2f8d8f4a08d6d1113394365daf670f1af9"
}
FILES,true);
$ok=true;
foreach($files as $file=>$hash){if(!is_file(__DIR__.'/'.$file)||hash_file('sha256',__DIR__.'/'.$file)!==$hash){echo 'Bestand ontbreekt of wijkt af: '.$file.PHP_EOL;$ok=false;}}
echo $ok?'Foto- en momentjesbalk-update volledig geplaatst.'.PHP_EOL:'Plaats de bestanden opnieuw in de juiste projectmap.'.PHP_EOL;
exit($ok?0:1);
