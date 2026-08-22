@echo off
title ATOM Personal AI - All-in-One Launcher
color 0A
cls
echo =======================================================
echo   ATOM Personal AI - All-in-One Quick Start Launcher
echo =======================================================
echo.

:: Step 1: Start Backend API Server
echo [Step 1/3] Starting Backend API Server on http://localhost:8080 ...
cd /d "%~dp0backend"
start cmd /k "title ATOM Backend API Server ^& cd /d "%~dp0backend" ^& php spark serve --host 0.0.0.0"

:: Wait for server to initialize
timeout /t 3 /nobreak >nul

:: Step 2: Run Self-Learning Evaluation via CLI
echo.
echo [Step 2/3] Executing Self-Learning Evaluation via CLI...
cd /d "%~dp0backend"
php spark atom:self-improve

:: Step 3: Launch Desktop WPF Assistant App
echo.
echo [Step 3/3] Launching Desktop WPF Assistant Application...
cd /d "%~dp0"
start cmd /k "title ATOM Desktop Assistant ^& cd /d "%~dp0" ^& dotnet run --project src/PersonalAIAssistant/PersonalAIAssistant.csproj"

echo.
echo =======================================================
echo   ✅ All ATOM services initialized successfully!
echo   • Backend API Server : http://localhost:8080
echo   • Self-Learning CLI  : Evaluation Finished
echo   • Desktop Assistant  : Launching WPF Client
echo =======================================================
echo.
pause
