<?php
return [
    'newsdata_key' => env('NEWSDATA_API_KEY'),
    'gemini_key' => env('GEMINI_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    'batch_size' => (int) env('NEWS_BATCH_SIZE', 5),
];
