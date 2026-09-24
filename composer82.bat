@echo off
setlocal
cd /d "%~dp0"
if exist "C:\xampp\php\php.exe" set "PATH=C:\xampp\php;%PATH%"
call composer %*
