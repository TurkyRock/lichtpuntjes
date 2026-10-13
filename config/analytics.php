<?php

return [
    // A measurement ID is public. Set enabled=false to turn off all Google requests.
    'enabled' => env('GOOGLE_ANALYTICS_ENABLED', true),
    'measurement_id' => env('GOOGLE_ANALYTICS_ID', 'G-3JSRH397WV'),
];
