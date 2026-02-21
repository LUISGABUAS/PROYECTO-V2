@echo off

:: Descargar NSSM si no existe
if not exist "%~dp0nssm.exe" (
    powershell -Command "Invoke-WebRequest -Uri 'https://nssm.cc/release/nssm-2.24.zip' -OutFile '%~dp0nssm.zip'"
    powershell -Command "Expand-Archive '%~dp0nssm.zip' -DestinationPath '%~dp0'"
    copy "%~dp0nssm-2.24\win64\nssm.exe" "%~dp0nssm.exe"
)

:: Instalar PrintServer como servicio
"%~dp0nssm.exe" install PrintServer "%~dp0PrintServer.exe"
"%~dp0nssm.exe" set PrintServer Start SERVICE_AUTO_START
net start PrintServer

echo ✅ PrintServer instalado como servicio
timeout /t 3