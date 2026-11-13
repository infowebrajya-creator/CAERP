#!/bin/bash
set -e

mkdir -p backups
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="backups/webrajya_backup_${TIMESTAMP}.sql"

echo "==================================================================="
echo "              WebRajya ERP — One-Click Backup Tool                 "
echo "==================================================================="
echo "Creating full database dump into ${BACKUP_FILE}..."

docker exec webrajya_mysql mysqldump -uwebrajya -pwebrajya_secure_pass webrajya > "${BACKUP_FILE}"

echo ""
echo "[SUCCESS] Backup complete: ${BACKUP_FILE}"
echo "Keep a copy on a USB drive or cloud backup."

