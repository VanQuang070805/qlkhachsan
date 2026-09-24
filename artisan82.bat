@echo off
setlocal
cd /d "%~dp0"
if defined PHP_BINARY (
    "%PHP_BINARY%" artisan %*
) else if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" artisan %*
) else (
    php artisan %*
)
