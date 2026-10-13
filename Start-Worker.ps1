$ErrorActionPreference='Stop'
Push-Location $PSScriptRoot
try { & php artisan queue:work database --queue=lichtpuntjes,default --timeout=1800 --tries=48 --sleep=3 } finally {Pop-Location}
