@echo off
chcp 65001 >nul
title WebRajya ERP — 1-Click Setup & Launcher
cls

echo ===================================================================
echo             WebRajya ERP — Practice Management System              
echo ===================================================================
echo.

:: 1. Check if Docker is installed and running
echo [1/3] Checking Docker installation...
docker info >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Docker is not running or not installed!
    echo.
    echo Please install Docker Desktop (free):
    echo 1. Download from: https://www.docker.com/products/docker-desktop/
    echo 2. Install and launch Docker Desktop from your Start Menu.
    echo 3. Run this install.bat again.
    echo.
    pause
    start https://www.docker.com/products/docker-desktop/
    exit /b 1
)
echo [OK] Docker is running properly!
echo.

:: 2. Build and launch containers
echo [2/3] Building & Starting WebRajya ERP containers...
echo (First time may take 2-4 minutes to download base images)
echo.
docker compose up -d --build

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Failed to start containers. Please verify Docker Desktop is running.
    pause
    exit /b 1
)

:: 3. Wait for database and application to spin up
echo.
echo [3/3] Initializing WebRajya application...
timeout /t 10 /nobreak >nul

echo.
echo ===================================================================
echo            SUCCESS: WebRajya ERP is now running!                   
echo ===================================================================
echo.
echo Application URL : http://localhost:8000
echo Activation URL  : http://localhost:8000/license/activate
echo.
echo Opening WebRajya ERP in your browser...
start http://localhost:8000/license/activate

echo.
echo NOTE: Keep Docker Desktop running in background while using WebRajya.
echo To stop WebRajya later, run: stop.bat
echo To start again later, run: start.bat
echo.
pause

