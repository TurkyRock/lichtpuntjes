<?php
return [
 'provider'=>env('NEWS_IMAGE_PROVIDER','cloudflare'),
 'cloudflare_account_id'=>env('CLOUDFLARE_ACCOUNT_ID'),
 'cloudflare_token'=>env('CLOUDFLARE_API_TOKEN'),
 'cloudflare_state_file'=>storage_path('app/cloudflare-illustration-requests.json'),
 'comfy_url'=>env('COMFYUI_URL','http://127.0.0.1:8188'),
 'checkpoint'=>env('COMFYUI_CHECKPOINT','sd_xl_base_1.0.safetensors'),
 'timeout'=>(int)env('COMFYUI_RENDER_TIMEOUT',900),
 'poll_seconds'=>(int)env('COMFYUI_POLL_SECONDS',2),
];
