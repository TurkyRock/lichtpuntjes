<?php
return [
 'enabled'=>(bool)env('LOCAL_AI_ENABLED',false),
 'vision_model'=>env('LOCAL_AI_VISION_MODEL','gemma3:4b'),
 'model'=>env('LOCAL_AI_MODEL','qwen3:8b'),
 'token_hash'=>env('LOCAL_AI_TOKEN_HASH',''),
 'lease_seconds'=>180,
 'retention_hours'=>24,
 'max_claims'=>3,
 // First evaluate the local model: approval/rejection of user text needs a human.
 'human_review'=>(bool)env('LOCAL_AI_HUMAN_REVIEW',true),
];
