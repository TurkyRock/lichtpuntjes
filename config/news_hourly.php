<?php
return [
    'enabled'=>(bool)env('NEWS_HOURLY_ENABLED',false),
    'per_category'=>(int)env('NEWS_HOURLY_PER_CATEGORY',3),
    'max_pages'=>(int)env('NEWS_HOURLY_MAX_PAGES',0),
    'max_seconds'=>3300,
    'assessment_delay'=>8,
    'state_file'=>storage_path('app/news-hourly/state.json'),
];
