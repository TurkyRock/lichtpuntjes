<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$files=json_decode(<<<'FILES'
{
  "app/Http/Controllers/CommunityController.php": "bd989c373d1f2f12134ec654ae415eba8ce3f56532c57a3aacaccee69bbef326",
  "public/css/account-scrapbook.css": "633306258bbd4e5deae653767761ab46cdce490af3fa887ca031ee1d5f772b29",
  "public/css/home-daily.css": "cfdfd2922a76a6b8e5500bce209113a7dccf67c5503700a73b86046dc60d07da",
  "public/css/profile-photos.css": "16777d63a18fa3df8a914f41829dfa7e35326b7ddf01dd3ba39b919b3c7f4bba",
  "public/images/account-paper-leaves.svg": "082b4cb92cf0507d233f73192626ff84119fb74bd56f5908c08d5861718c1730",
  "public/images/account-paper-sun.svg": "748d210ece442558b209e8a7dd9342e06a32c0f34420ce07b614a800024a762c",
  "public/images/button-paper-grain.svg": "cae4007b0539a29e46bb9ba54492373ebac9c77b8d23d612aaaa06c2f967e87c",
  "resources/views/community/layout.blade.php": "fde0b8ba70759fb0e32df223a4f50d383fc9e42fc7f6b2ad90ab9dc6f358da27",
  "resources/views/community/profile-photo.blade.php": "b8b036bccf8630d01e4fa6b8d5b9572caffe9fc8e17590dd31df40424184c937",
  "resources/views/community/profile-content.blade.php": "2145cc6edce4a93e7e79811cbf970d84996798044e5209e01282f7cdc9d59017",
  "resources/views/community/account-moment-preview.blade.php": "7bfc848861ab282e3499c44ec7ab64446f3a08e4cae23b701340cf5ce6f40356",
  "resources/views/community/moments.blade.php": "c66159e42afc952aceb5ba5e31f7e5000a3863ba02a509c1db5e23d27dd9503c",
  "resources/views/community/lightspot-write.blade.php": "b6a0d484540523a497423669e7f745bce34c6aeafd56acb4f38c7b0f30d89967",
  "resources/views/shared/account-sidebar.blade.php": "cff61f92ffe9b4aa586f3c511fc9f872b6509db8b0a7a4e9f31ced2422dfac2f",
  "resources/views/shared/ai-image-label.blade.php": "eb4b5d59026cfb1dfcd559a216d92f6114fe98ff4ee7e5e42eab356c70ff1ddb",
  "resources/views/homepage/highlights.blade.php": "5b7886afdac409fd0e2bf60fa7ee9637942f90c4e8928acff4bc58aefbc77d4e",
  "resources/views/homepage/moments.blade.php": "82836fbb2653c776fb2b1c0ddba0b2412bff3f979c4c708f23d9f29263202ead",
  "resources/views/homepage/our-story.blade.php": "405941b23314d681caee8c42fb9b7c530a3a9ddfe063cb3afcd2dacd15fa0b1f",
  "resources/views/seo/cards.blade.php": "71a55a6c9b0bc3dd9aac6a61e925437b0a8bf8844f0200fa8e724705a1bf6b3a",
  "resources/views/lichtpunt.blade.php": "e92b7c767705b8beaf2b900cd42b9abd0e2c910b40ce02d285775b7125776efe",
  "public/images/scrap-paper-ivory.webp": "654a68e6457745c5956f255b6e32d439936b80514974e65c1bd0cbf086dc0c81",
  "public/images/scrap-paper-sage.webp": "a29786f2ebe7ed3cccad5f4f44de2de4d82edd3c8a4859befa3dd0263535377c",
  "public/images/scrap-paper-yellow.webp": "7577204d2279d624db57f7909a01caac2f1912586db5b5af41a329be15ac937a",
  "public/images/scrap-daisy.webp": "c09d8c25ba8177ca9e3496489a5872c7d42a91e692a0654cd2f4f96f850ece2d",
  "public/images/moment-rope.svg": "96a609dfa00b8f7df659a7f71345dcedb004bb8e0cfcc46846458e9b82ef4015",
  "public/images/moment-clothespin.svg": "acec0e2ea23676e7d9961358d86d57e956bd615b89de00238ea277b54c42aef7",
  "public/js/moment-washing-line.js": "ffa945d69462ee86d5aa52a87fbdd09779b5f20db4b49e88f2e998d007e763f8",
  "app/Services/CommunityDemo.php": "54035d0119351cd3a11642588baaaad487cab167658b2e15203a1792a4964a69",
  "app/Console/Commands/SeedCommunityDemo.php": "58b8b7af4e2e7f8ac118ac0c8aedcee3c3b91f66281818140d43161284e12d7d",
  "app/Http/Controllers/PublicStoryController.php": "10be7cbbdb70e529ae92275f607402e361e1b1be6021de14bdf0b18594e10501",
  "resources/views/shared/admin-sidebar.blade.php": "ce9fcc6a8c0187ada14e7cb59e7a88de35fcd0832d8decb81d787c6f6d370129",
  "resources/views/community/privacy-requests.blade.php": "8abd40bfeaffd13cc36dde95a83c1b078d857c1857686e7e0d3bb84ffb4abca6",
  "database/demo-portraits/sanne.jpg": "09c55b1fc310de9cccce9c11c5791ef666a0006ceea2e84d4cb1e9747c00d380",
  "database/demo-portraits/youssef.jpg": "0aec90c5df7d48c2e9e129fa0aef8706ffe18e785f1473928aadddcc714e2599",
  "database/demo-portraits/marieke.jpg": "be8dc8d2595f285b4227ec012e44c51e04830589e491b0af37b81f76f225a3c0",
  "database/demo-portraits/tom.jpg": "60a2193ce0cb522da2b26bdc6ae8e7c786026d2a1d85c0d1bcaa4536497d7762"
}
FILES,true);
$ok=true;
foreach($files as $file=>$hash){if(!is_file(__DIR__.'/'.$file)||hash_file('sha256',__DIR__.'/'.$file)!==$hash){echo 'Bestand ontbreekt of wijkt af: '.$file.PHP_EOL;$ok=false;}}
echo $ok?'Voorbeeldgebruikers, knipselboek en beheer volledig geplaatst.'.PHP_EOL:'Plaats de bestanden opnieuw in de juiste projectmap.'.PHP_EOL;
exit($ok?0:1);
