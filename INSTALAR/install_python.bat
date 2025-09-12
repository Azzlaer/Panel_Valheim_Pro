@echo off
echo === Instalador de Python ===

set PYURL=https://www.python.org/ftp/python/3.12.6/python-3.12.6-amd64.exe
set INSTALLER=%TEMP%\python-installer.exe

echo Descargando instalador de Python...
powershell -Command "Invoke-WebRequest -Uri %PYURL% -OutFile %INSTALLER%"

echo Instalando Python en silencio...
%INSTALLER% /quiet InstallAllUsers=1 PrependPath=1 Include_pip=1

echo Verificando instalación...
python --version
pip --version

echo Python y pip instalados correctamente.
pause
