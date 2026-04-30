@echo off
NET SESSION >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo Solicitando permisos de administrador...
    powershell -Command "Start-Process '%~f0' -Verb RunAs"
    exit /b
)

SET NOMBRE=PrintServerZebra
SET EXE=%~dp0PrintServer.exe

:: Registrar tarea programada que arranca con Windows (como SYSTEM)
schtasks /create /tn "%NOMBRE%" /tr "\"%EXE%\"" /sc onstart /ru SYSTEM /rl HIGHEST /f >nul 2>&1

:: Iniciar ahora mismo si no está corriendo
tasklist /fi "imagename eq PrintServer.exe" 2>nul | find /i "PrintServer.exe" >nul
IF %ERRORLEVEL% NEQ 0 (
    start "" "%EXE%"
)

echo.
echo PrintServer instalado. Arrancara automaticamente con Windows.
echo.
timeout /t 3 >nul
