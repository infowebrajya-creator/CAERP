@echo off
title Starting WebRajya ERP
cls
echo Starting WebRajya ERP containers...
docker compose up -d
echo Opening WebRajya in browser...
timeout /t 3 /nobreak >nul
start http://localhost:8000

