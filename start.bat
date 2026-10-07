@echo off
setlocal
cd /d "%~dp0"
echo Menjalankan aplikasi dalam mode lokal di http://127.0.0.1:8000
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start.ps1" -Tunnel
if errorlevel 1 pause
