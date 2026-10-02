$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$logDirectory = Join-Path $projectRoot 'storage\logs'
$startupLog = Join-Path $logDirectory 'background-services.log'

New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null

function Write-StartupLog {
    param([string] $Message)

    Add-Content -LiteralPath $startupLog -Value "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] $Message"
}

function Test-TcpPort {
    param(
        [int] $Port,
        [int] $TimeoutMilliseconds = 500
    )

    $client = [System.Net.Sockets.TcpClient]::new()
    try {
        $connection = $client.ConnectAsync('127.0.0.1', $Port)
        return $connection.Wait($TimeoutMilliseconds) -and $client.Connected
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

function Wait-ForPort {
    param(
        [int] $Port,
        [int] $TimeoutSeconds = 20
    )

    $deadline = (Get-Date).AddSeconds($TimeoutSeconds)
    do {
        if (Test-TcpPort -Port $Port) {
            return $true
        }
        Start-Sleep -Milliseconds 500
    } while ((Get-Date) -lt $deadline)

    return $false
}

function Start-HiddenService {
    param(
        [string] $Name,
        [string] $Executable,
        [string[]] $Arguments,
        [string] $WorkingDirectory,
        [string] $LogPrefix
    )

    $stdout = Join-Path $logDirectory "$LogPrefix.stdout.log"
    $stderr = Join-Path $logDirectory "$LogPrefix.stderr.log"
    $process = Start-Process -FilePath $Executable `
        -ArgumentList $Arguments `
        -WorkingDirectory $WorkingDirectory `
        -WindowStyle Hidden `
        -RedirectStandardOutput $stdout `
        -RedirectStandardError $stderr `
        -PassThru

    Write-StartupLog "$Name started in background (PID $($process.Id))."
}

try {
    if (-not (Test-TcpPort -Port 3306)) {
        $mysql = 'C:\xampp\mysql\bin\mysqld.exe'
        if (-not (Test-Path -LiteralPath $mysql)) {
            throw 'MySQL was not found at C:\xampp\mysql\bin\mysqld.exe.'
        }

        Start-HiddenService -Name 'MySQL' `
            -Executable $mysql `
            -Arguments @('--defaults-file=C:\xampp\mysql\bin\my.ini', '--standalone') `
            -WorkingDirectory (Split-Path -Parent $mysql) `
            -LogPrefix 'mysql'

        if (-not (Wait-ForPort -Port 3306 -TimeoutSeconds 20)) {
            throw 'MySQL did not become ready on port 3306.'
        }
    }

    if (-not (Test-TcpPort -Port 8001)) {
        $faceDirectory = Join-Path $projectRoot 'face_recognition\pc'
        $facePython = Join-Path $faceDirectory '.venv\Scripts\python.exe'
        if (-not (Test-Path -LiteralPath $facePython)) {
            throw 'Face ID PC service is not installed. Read face_recognition\README.md first.'
        }

        Start-HiddenService -Name 'Face ID' `
            -Executable $facePython `
            -Arguments @('run_api.py') `
            -WorkingDirectory $faceDirectory `
            -LogPrefix 'face-id'

        if (-not (Wait-ForPort -Port 8001 -TimeoutSeconds 25)) {
            throw 'Face ID did not become ready on port 8001.'
        }
    }

    $php = if (Test-Path -LiteralPath 'C:\xampp\php\php.exe') {
        'C:\xampp\php\php.exe'
    } else {
        (Get-Command php -ErrorAction Stop).Source
    }

    $schedulerRunning = Get-CimInstance Win32_Process | Where-Object {
        $_.Name -like 'php*' -and $_.CommandLine -like '*artisan*schedule:work*'
    } | Select-Object -First 1

    if (-not $schedulerRunning) {
        Start-HiddenService -Name 'Laravel scheduler' `
            -Executable $php `
            -Arguments @('artisan', 'schedule:work') `
            -WorkingDirectory $projectRoot `
            -LogPrefix 'scheduler'
    }

    if (-not (Test-TcpPort -Port 8000)) {
        Start-HiddenService -Name 'Laravel' `
            -Executable $php `
            -Arguments @('artisan', 'serve', '--host=0.0.0.0', '--port=8000') `
            -WorkingDirectory $projectRoot `
            -LogPrefix 'laravel-server'

        if (-not (Wait-ForPort -Port 8000 -TimeoutSeconds 20)) {
            throw 'Laravel did not become ready on port 8000.'
        }
    }

    Write-StartupLog 'All Royal Hotel services are ready.'
} catch {
    Write-StartupLog "ERROR: $($_.Exception.Message)"
    throw
}
