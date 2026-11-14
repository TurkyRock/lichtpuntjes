<?php
return [
 'enabled'=>(bool)env('RETENTION_ENABLED',true),
 'archive_days'=>90, 'trash_days'=>30, 'import_days'=>30, 'orphan_days'=>7, 'temporary_days'=>1, 'job_days'=>30, 'log_days'=>14,
 'quota_bytes'=>(int)env('SITE_STORAGE_QUOTA_MB',2048)*1024*1024,
 // Subscription directory includes files outside httpdocs. Plesk database size is added separately.
 'usage_root'=>env('SITE_STORAGE_ROOT',dirname(base_path())),
 'warning_percent'=>70, 'pause_percent'=>85,
 'max_items'=>500,
 'backup_remote'=>env('SITE_BACKUP_REMOTE',''),
 'backup_key'=>env('SITE_BACKUP_KEY',''),
 'backup_days'=>14,
];
