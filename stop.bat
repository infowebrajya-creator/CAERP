@echo off
title Stopping WebRajya ERP
cls
echo Safely stopping WebRajya ERP containers...
docker compose stop
echo.
echo Containers stopped. Your data is safely stored in local volumes.
pause

