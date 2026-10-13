<?php
// These OAuth credentials are separate from news/Gemini API keys.
return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim(env('APP_URL', ''), '/').'/auth/google/callback'),
    ],
    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID', ''),
        'client_secret' => '',
        'team_id' => env('APPLE_TEAM_ID', ''),
        'key_id' => env('APPLE_KEY_ID', ''),
        'private_key' => env('APPLE_PRIVATE_KEY_PATH', storage_path('app/private/apple/AuthKey.p8')),
        'redirect' => env('APPLE_REDIRECT_URI', rtrim(env('APP_URL', ''), '/').'/auth/apple/callback'),
    ],
    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID', ''),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET', ''),
        'redirect' => env('MICROSOFT_REDIRECT_URI', rtrim(env('APP_URL', ''), '/').'/auth/microsoft/callback'),
        'tenant' => 'common',
        'include_avatar' => false,
        'include_tenant_info' => false,
    ],
];
