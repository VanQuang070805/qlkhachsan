$ErrorActionPreference = 'Stop'

$principal = [Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Run this script in PowerShell as Administrator. It creates one narrowly scoped inbound rule.'
}

$ruleName = 'Royal Hotel Dify room tools (Docker Desktop only)'
if (Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue) {
    throw "A firewall rule named '$ruleName' already exists. Inspect it before changing anything."
}

New-NetFirewallRule `
    -DisplayName $ruleName `
    -Description 'Allow the Dify Docker Desktop network to reach only the Royal Hotel read-only tool bridge on TCP 8001.' `
    -Direction Inbound `
    -Action Allow `
    -Protocol TCP `
    -LocalPort 8001 `
    -RemoteAddress '192.168.65.0/24' `
    -Program 'D:\xampp\php\php.exe' `
    -Profile Any | Out-Null

Write-Output 'Firewall rule created: TCP 8001, only from Docker Desktop 192.168.65.0/24, only for XAMPP PHP.'
