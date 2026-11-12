@echo off
title WebRajya ERP — Local Database Backup
cls
if not exist "backups" mkdir "backups"

for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set TIMESTAMP=%datetime:~0,8%_%datetime:~8,6%
set BACKUP_FILE=backups\webrajya_backup_%TIMESTAMP%.sql

echo ===================================================================
echo               WebRajya ERP — One-Click Backup Tool                 
echo ===================================================================
echo.
echo Creating full SQL database dump into %BACKUP_FILE%...

docker exec webrajya_mysql mysqldump -uwebrajya -pwebrajya_secure_pass webrajya > "%BACKUP_FILE%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo [SUCCESS] Backup created successfully!
    echo Location: %BACKUP_FILE%
    echo Tip: Store a copy of this file on a secure USB drive or Google Drive.
) else (
    echo.
    echo [ERROR] Backup failed. Is WebRajya ERP running?
)

echo.
pause
