$ErrorActionPreference = "Stop"

& "$PSScriptRoot\preflight.ps1"
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

Write-Host "[Setup] Starting royal-hotel-nhom10-v2 Docker stack..." -ForegroundColor Cyan
docker compose --env-file .env.docker up -d --build

Write-Host "`n[Setup] Rosaliza Hotel Portals:" -ForegroundColor Green
Write-Host "  - Customer Portal : http://localhost:18080"
Write-Host "  - Staff Portal    : http://localhost:18081"
Write-Host "  - Admin Portal    : http://localhost:18082"
