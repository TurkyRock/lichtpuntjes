<?php
return [
    'enabled' => true,
    'timezone' => 'Europe/Amsterdam',
    'pages' => 3,
    'assess_limit' => 30,
    'publish_limit' => 6,
    'image_budget_enabled' => true,
    'news_images' => max(0,(int)env('NEWS_IMAGES_DAILY_LIMIT',30)),
    'community_images' => 15,
    'cloudflare_attempts' => max(0,(int)env('CLOUDFLARE_DAILY_IMAGE_REQUEST_LIMIT',50)),
    'budget_file' => storage_path('app/illustration-budget.json'),
];
