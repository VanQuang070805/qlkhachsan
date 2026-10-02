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

rem Services are started as hidden background processes by PowerShell.
powershell -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File "%~dp0scripts\start-background-services.ps1"

if errorlevel 1 (
    echo Unable to start Royal Hotel services. Check storage\logs\background-services.log.
    exit /b 1
)

echo Royal Hotel is running in the background at http://localhost:8000/internalauth/login
