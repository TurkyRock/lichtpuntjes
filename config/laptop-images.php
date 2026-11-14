<?php
return [
 'enabled'=>env('LAPTOP_IMAGES_ENABLED',false),
 // Generate locally with: php artisan laptop:token. Store only its SHA-256 hash here.
 'token_hash'=>env('LAPTOP_IMAGE_TOKEN_HASH',''),
 'lease_seconds'=>180,
 'max_claims'=>5,
 'max_upload_bytes'=>12*1024*1024,
];
