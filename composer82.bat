@echo off
set "PHP82_DIR=C:\Users\admin\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe"
set "PATH=%PHP82_DIR%;%PATH%"
composer %*
