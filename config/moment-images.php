<?php
return [
    'enabled' => env('MOMENT_IMAGES_ENABLED', true),
    'daily_limit' => (int) env('MOMENT_IMAGES_PER_DAY', 6),
    'budget_file' => storage_path('app/moment-image-budget.json'),
];
