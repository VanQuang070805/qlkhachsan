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
echo Open http://localhost:8000/internalauth/login
call "%~dp0artisan82.bat" serve --host=127.0.0.1 --port=8000
