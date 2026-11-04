param(
    [int]$CustomerPort = 18080,
    [int]$StaffPort = 18081,
    [int]$AdminPort = 18082
)

$ErrorActionPreference = "Stop"

Write-Host "[Preflight] Checking Rosaliza Hotel Docker environment (project: royal-hotel-nhom10-v2)..." -ForegroundColor Cyan

# 1. Verify .env.docker exists
if (-not (Test-Path ".env.docker")) {
    Write-Host "[Preflight] Creating .env.docker from .env.docker.example..." -ForegroundColor Yellow
    Copy-Item ".env.docker.example" ".env.docker"
}

# 2. Check if target ports are free
foreach ($port in @($CustomerPort, $StaffPort, $AdminPort)) {
    $inUse = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
    if ($inUse) {
        Write-Error "[Preflight] Port $port is already in use by PID $($inUse[0].OwningProcess). Please change CUSTOMER_PORT/STAFF_PORT/ADMIN_PORT in .env.docker before starting."
        exit 1
    }
}

Write-Host "[Preflight] Ports $CustomerPort, $StaffPort, $AdminPort are free." -ForegroundColor Green

# 3. Validate docker compose syntax
docker compose --env-file .env.docker config -q
if ($LASTEXITCODE -ne 0) {
    Write-Error "[Preflight] docker compose config validation failed."
    exit 1
}

Write-Host "[Preflight] Compose configuration is valid. Ready to run: docker compose --env-file .env.docker up -d --build" -ForegroundColor Green
