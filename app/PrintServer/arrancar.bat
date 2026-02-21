@echo off

:: Copiar a startup automaticamente
copy "%~dp0PrintServer.exe" "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\PrintServer.exe" /Y

:: Iniciar ahora mismo en segundo plano
start /min "" "%~dp0PrintServer.exe"

echo ✅ Instalado y ejecutando
timeout /t 2