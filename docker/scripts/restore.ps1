param(
    [Parameter(Mandatory = $true)]
    [string]$BackupFile,
    [Parameter(Mandatory = $true)]
    [string]$ConfirmDatabase
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $BackupFile)) {
    Write-Error "[Restore] Backup file not found: $BackupFile"
    exit 1
}

if ($ConfirmDatabase -ne "qlkhachsan") {
    Write-Error "[Restore] Safety check failed: -ConfirmDatabase must match 'qlkhachsan'."
    exit 1
}

Write-Host "[Restore] Restoring $BackupFile into database $ConfirmDatabase..." -ForegroundColor Yellow
Get-Content $BackupFile -Raw | docker compose --env-file .env.docker exec -T mysql sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
Write-Host "[Restore] Restore completed successfully." -ForegroundColor Green
