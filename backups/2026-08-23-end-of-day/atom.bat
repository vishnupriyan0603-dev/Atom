@echo off
title Atom Terminal
:MENU
cls
echo ========================================
echo   Atom - Terminal Tools
echo ========================================
echo.
echo   [A] Start All Services (Backend + CLI + Desktop UI)
echo   [N] Create New Project (Step-by-Step Wizard)
echo   [1] Start Server
echo   [2] Stop Server
echo   [3] Run Migrations
echo   [4] Seed Local Models
echo   [5] Add Custom Model
echo   [6] List Models
echo   [7] Clean Cloud Models
echo   [8] Delete Model
echo   [9] Check Server Status
echo   [W] Setup WSL Ubuntu AI Server
echo   [0] Exit
echo.
set /p CHOICE="Select: "

if /i "%CHOICE%"=="A" goto STARTALL
if /i "%CHOICE%"=="N" goto NEWPROJECT
if "%CHOICE%"=="1" goto START
if "%CHOICE%"=="2" goto STOP
if "%CHOICE%"=="3" goto MIGRATE
if "%CHOICE%"=="4" goto SEED
if "%CHOICE%"=="5" goto ADDMODEL
if "%CHOICE%"=="6" goto LIST
if "%CHOICE%"=="7" goto CLEAN
if "%CHOICE%"=="8" goto DELETE
if "%CHOICE%"=="9" goto STATUS
if /i "%CHOICE%"=="W" goto WSL
if "%CHOICE%"=="0" exit
goto MENU

:STARTALL
cls
call "%~dp0start-all.bat"
goto MENU

:START
cls
echo Starting server on http://localhost:8080 ...
cd /d "%~dp0backend"
start cmd /k "title Atom Server ^& php spark serve --host 0.0.0.0"
echo.
echo Server started. Press any key to return to menu.
pause >nul
goto MENU

:STOP
cls
echo Stopping all PHP processes...
taskkill /F /IM php.exe 2>nul
echo Done.
pause
goto MENU

:MIGRATE
cls
echo Running migrations...
cd /d "%~dp0backend"
php spark migrate --force
pause
goto MENU

:SEED
cls
echo Seeding local models only...
cd /d "%~dp0backend"
php spark db:seed AiModelSeeder
pause
goto MENU

:ADDMODEL
cls
call "%~dp0add-model.bat"
goto MENU

:LIST
cls
echo Listing models from database...
cd /d "%~dp0backend"
php spark db:seed ListModels
pause
goto MENU

:CLEAN
cls
echo Removing all cloud models...
cd /d "%~dp0backend"
php spark db:seed CleanModels
pause
goto MENU

:DELETE
cls
echo Delete a model...
cd /d "%~dp0backend"
php spark db:seed ListModels
echo.
php spark db:seed DeleteModel
pause
goto MENU

:STATUS
cls
echo Checking server status...
curl -s http://localhost:8080/ 2>nul
if %errorlevel%==0 (
    echo.
    echo [OK] Server is running
) else (
    echo.
    echo [OFFLINE] Server is not running
)
pause
goto MENU

:WSL
cls
echo ========================================
echo   Setup WSL Ubuntu AI Server
echo ========================================
echo.
echo This will install Ollama in WSL Ubuntu
echo and start a local AI server.
echo.
echo Prerequisites:
echo   - WSL Ubuntu installed (run: wsl --install -d Ubuntu)
echo.
set /p CONFIRM="Continue? (Y/N): "
if /i not "%CONFIRM%"=="Y" goto MENU
echo.
echo Starting WSL setup...
wsl bash -c "curl -fsSL https://raw.githubusercontent.com/ollama/ollama/main/install.sh | sh"
wsl bash -c "ollama serve &"
wsl bash -c "ollama pull llama3.1"
echo.
echo Done! AI server running on localhost:11434
echo Add model: [5] Add Custom Model
pause
goto MENU

:NEWPROJECT
cls
echo Launching Step-by-Step New Project Wizard...
cd /d "%~dp0"
php bin/atom /new-project
echo.
pause
goto MENU
