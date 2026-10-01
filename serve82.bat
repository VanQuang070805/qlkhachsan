@echo off
setlocal
cd /d "%~dp0"
if not exist ".env" (
    echo Missing .env. Read docs\TEST-PC.md first.
    exit /b 1
)
if not exist "vendor\autoload.php" (
    echo Run composer82.bat install first.
    exit /b 1
)
powershell -NoProfile -ExecutionPolicy Bypass -Command "if (Test-NetConnection 127.0.0.1 -Port 3306 -InformationLevel Quiet) { exit 0 } else { exit 1 }" >nul 2>&1
if errorlevel 1 (
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        echo Starting MySQL on 127.0.0.1:3306
        start "MySQL" /min "C:\xampp\mysql\bin\mysqld.exe" "--defaults-file=C:\xampp\mysql\bin\my.ini" "--standalone"
        timeout /t 4 /nobreak >nul
    ) else (
        echo MySQL was not found at C:\xampp\mysql\bin\mysqld.exe.
    )
)
powershell -NoProfile -ExecutionPolicy Bypass -Command "if (Test-NetConnection 127.0.0.1 -Port 8001 -InformationLevel Quiet) { exit 0 } else { exit 1 }" >nul 2>&1
if errorlevel 1 (
    if exist "face_recognition\pc\.venv\Scripts\python.exe" (
        echo Starting Face ID PC service on http://127.0.0.1:8001
        start "Face ID PC Service" /min "%~dp0face_recognition\pc\.venv\Scripts\python.exe" "%~dp0face_recognition\pc\run_api.py"
    ) else (
        echo Face ID PC service is not installed. Read face_recognition\README.md first.
    )
)
powershell -NoProfile -ExecutionPolicy Bypass -Command "$deadline = (Get-Date).AddSeconds(20); do { try { $health = Invoke-RestMethod -Uri 'http://127.0.0.1:8001/api/health' -TimeoutSec 2; if ($health.status -eq 'ok') { exit 0 } } catch {}; Start-Sleep -Milliseconds 500 } while ((Get-Date) -lt $deadline); exit 1" >nul 2>&1
if errorlevel 1 (
    echo Face ID PC service did not become ready on port 8001.
    echo Check face_recognition\pc and try again.
    exit /b 1
)
echo Face ID PC service is ready.
set "SCHEDULER_RUNNING="
for /f "delims=" %%P in ('powershell -NoProfile -Command "Get-CimInstance Win32_Process | Where-Object { $_.Name -like 'php*' -and $_.CommandLine -like '*artisan*schedule:work*' } | Select-Object -First 1 -ExpandProperty ProcessId"') do set "SCHEDULER_RUNNING=%%P"
if not defined SCHEDULER_RUNNING (
    echo Starting Laravel scheduler for Face ID sync retries
    start "Royal Hotel Scheduler" /min cmd /c call "%~dp0artisan82.bat" schedule:work
) else (
    echo Laravel scheduler is already running.
)
echo Open http://localhost:8000/internalauth/login
call "%~dp0artisan82.bat" serve --host=0.0.0.0 --port=8000
