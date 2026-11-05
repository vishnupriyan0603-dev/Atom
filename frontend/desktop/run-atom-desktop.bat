@echo off
setlocal enabledelayedexpansion
title ATOM AI Assistant - Desktop Shell Launcher

:: 1. Ensure Backend REST API Server is running on port 8080
netstat -ano | findstr ":8080" >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ATOM] Starting Backend REST API Server in background...
    start "ATOM Backend API Server" /min cmd /c "cd /d "%~dp0..\..\backend" && php -d extension=intl -d extension=sqlite3 -d extension=pdo_sqlite -S 0.0.0.0:8080 -t public vendor\codeigniter4\framework\system\rewrite.php"
    timeout /t 2 /nobreak >nul
)

:: 2. Search for modern browser app-mode runners
set "APP_RUNNER="
set "APP_URL=http://localhost:8080/desktop"
set "APP_FILE=%~dp0index.html"
set "HTA_FILE=%~dp0Atom.hta"

:: Check Microsoft Edge
if exist "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" (
    set "APP_RUNNER=%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe"
) else if exist "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" (
    set "APP_RUNNER=%ProgramFiles%\Microsoft\Edge\Application\msedge.exe"
) else if exist "%LocalAppData%\Microsoft\Edge\Application\msedge.exe" (
    set "APP_RUNNER=%LocalAppData%\Microsoft\Edge\Application\msedge.exe"
)

:: Check Google Chrome if Edge not found
if "!APP_RUNNER!"=="" (
    if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" (
        set "APP_RUNNER=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
    ) else if exist "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" (
        set "APP_RUNNER=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
    ) else if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" (
        set "APP_RUNNER=%LocalAppData%\Google\Chrome\Application\chrome.exe"
    )
)

:: 3. Launch Strategy
if not "!APP_RUNNER!"=="" (
    start "" "!APP_RUNNER!" --app="%APP_URL%" --window-size=1200,800 --window-position=80,60 --user-data-dir="%LocalAppData%\AtomDesktop\profile" --disable-infobars --autoplay-policy=no-user-gesture-required --enable-features=SpeechRecognition
    exit /b 0
)

:: 4. Fallback to Windows MSHTA Host
if exist "%SystemRoot%\System32\mshta.exe" (
    start "" "%SystemRoot%\System32\mshta.exe" "%HTA_FILE%"
    exit /b 0
)

:: 5. Fallback to Default Browser
start "" "%APP_URL%"
exit /b 0
