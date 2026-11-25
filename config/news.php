<?php
return [
    'newsdata_key' => env('NEWSDATA_API_KEY'),
    'use_local_text_ai' => (bool) env('NEWS_USE_LOCAL_TEXT_AI', false) && (bool) env('LOCAL_AI_ENABLED', false),
    'gemini_key' => env('GEMINI_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    // Two Dutch-source collection starts, then one worldwide, per category.
    'dutch_first' => (bool) env('NEWS_DUTCH_FIRST', true),
    'batch_size' => (int) env('NEWS_BATCH_SIZE', 5),
];
