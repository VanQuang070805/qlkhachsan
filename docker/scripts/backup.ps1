param(
    [string]$OutputDir = "docker/backups"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$outFile = Join-Path $OutputDir "qlkhachsan_backup_$timestamp.sql"

Write-Host "[Backup] Exporting MySQL database from container royal-hotel-nhom10-v2-mysql-1 to $outFile..." -ForegroundColor Cyan
docker compose --env-file .env.docker exec -T mysql sh -c 'mysqldump --single-transaction -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > $outFile

$hash = (Get-FileHash $outFile -Algorithm SHA256).Hash
Write-Host "[Backup] Completed: $outFile (SHA256: $hash)" -ForegroundColor Green
