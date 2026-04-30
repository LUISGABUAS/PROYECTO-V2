@echo off
NET SESSION >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo ERROR: Ejecuta este archivo como Administrador.
    pause
    exit /b 1
)

SET DIR=%~dp0
SET NSSM=%DIR%nssm.exe
SET NOMBRE=PrintServerZebra

IF NOT EXIST "%NSSM%" (
    echo ERROR: No se encontro nssm.exe
    pause
    exit /b 1
)

echo Deteniendo servicio "%NOMBRE%"...
"%NSSM%" stop "%NOMBRE%"

echo Eliminando servicio...
"%NSSM%" remove "%NOMBRE%" confirm

echo.
echo Servicio eliminado correctamente.
echo.
pause
