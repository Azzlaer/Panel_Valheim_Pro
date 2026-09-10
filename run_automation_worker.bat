@echo off
setlocal
cd /d "%~dp0"
where php >nul 2>nul
if %errorlevel%==0 (
    php automation_worker.php
    exit /b %errorlevel%
)
if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" automation_worker.php
    exit /b %errorlevel%
)
echo [ERROR] No se encontro php.exe. Agrega PHP al PATH o ajusta este BAT.
exit /b 1
