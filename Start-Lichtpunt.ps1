param([Parameter(Mandatory=$true)][string]$OldProjectPath)
$ErrorActionPreference='Stop'
$project=$PSScriptRoot
if(!(Test-Path (Join-Path $project 'artisan'))){throw 'Start dit script vanuit het uitgepakte nieuwe project.'}
if(Test-Path (Join-Path $project '.env')){throw 'Deze nieuwe map is al ingesteld. Gebruik een verse uitgepakte map voor de overstap.'}
if([IO.Path]::GetFullPath($OldProjectPath).TrimEnd('\') -eq $project.TrimEnd('\')){throw 'Oude en nieuwe map moeten verschillend zijn.'}
Get-Command php -ErrorAction Stop | Out-Null
Get-Command composer -ErrorAction Stop | Out-Null
Push-Location $project
try {
 & composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts
 if($LASTEXITCODE -ne 0){throw 'Composer installeren mislukt. De oude site is ongewijzigd.'}
 & php tools/prepare-existing.php $OldProjectPath
 if($LASTEXITCODE -ne 0){throw 'Gegevensovername gestopt. Gebruik na herstel een nieuwe lege projectmap.'}
 & php artisan package:discover --no-interaction
 if($LASTEXITCODE -ne 0){throw 'Pakketten laden mislukt.'}
 & php artisan migrate --force --no-interaction
 if($LASTEXITCODE -ne 0){throw 'Migratie van de databasekopie mislukt. De originele database is behouden.'}
 & php artisan view:cache
 if($LASTEXITCODE -ne 0){throw 'Views voorbereiden mislukt.'}
 & php artisan lichtpuntjes:controle
 if($LASTEXITCODE -ne 0){Write-Warning 'Controle meldt ontbrekende instellingen; bekijk de melding.'}
} finally {Pop-Location}
Write-Host 'Nieuwe projectmap gereed. Stel Herd in op deze map/public; lees START-HIER.md.'
Write-Host 'Je oorspronkelijke projectmap en database zijn niet vervangen.'
