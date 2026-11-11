param()

$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$chatbotRoot = Join-Path $projectRoot 'services\royal-ai-chatbot'
$infraCompose = Join-Path $projectRoot 'compose.local-infra.yaml'
$docker = (Get-Command docker.exe -ErrorAction Stop).Source
$npm = (Get-Command npm.cmd -ErrorAction Stop).Source

& (Join-Path $PSScriptRoot 'configure-local-infra.ps1')

& $docker info --format '{{.ServerVersion}}' *> $null
if ($LASTEXITCODE -ne 0) {
    $desktop = Join-Path $env:LOCALAPPDATA 'Programs\DockerDesktop\Docker Desktop.exe'
    if (-not (Test-Path -LiteralPath $desktop)) { throw 'Docker Desktop is required for the local Docker stack.' }
    Start-Process -FilePath $desktop -WindowStyle Hidden
    $dockerReady = $false
    for ($attempt = 0; $attempt -lt 60; $attempt++) {
        Start-Sleep -Seconds 1
        & $docker info --format '{{.ServerVersion}}' *> $null
        if ($LASTEXITCODE -eq 0) { $dockerReady = $true; break }
    }
    if (-not $dockerReady) { throw 'Docker Engine did not become ready.' }
}

& $npm run build --workspace backend --prefix $chatbotRoot | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Chatbot TypeScript build failed.' }

& $docker compose -f $infraCompose up -d
if ($LASTEXITCODE -ne 0) { throw 'Docker stack failed to start.' }

Write-Host 'Ready (All-in-Docker rosaliza-local-infra): customer http://127.0.0.1:18080 | staff :18081 | admin :18082 | internal :18084 | chatbot :2024 | MySQL :3306 | Redis :16379/:16380 | Kafka :19092'
