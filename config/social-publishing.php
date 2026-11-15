<?php
return ['timezone'=>'Europe/Amsterdam','planner_enabled'=>(bool)env('SOCIAL_PLANNER_ENABLED',false),
 'slots'=>['daily'=>['time'=>'07:05','label'=>'Daglichtpuntje','intro'=>'Eén van onze lichtpuntjes van de dag'], 'reads'=>['time'=>'20:30','label'=>'Meest gelezen','intro'=>'Eén van onze meest gelezen artikelen van vandaag'], 'likes'=>['time'=>'21:00','label'=>'Meest geliefd','intro'=>'Eén van onze meest geliefde artikelen van vandaag']],
 'channels'=>['facebook'=>['label'=>'Facebook','width'=>1200,'height'=>630], 'instagram'=>['label'=>'Instagram','width'=>1080,'height'=>1350], 'linkedin'=>['label'=>'LinkedIn','width'=>1200,'height'=>627], 'tiktok'=>['label'=>'TikTok','width'=>1080,'height'=>1920]],
];
