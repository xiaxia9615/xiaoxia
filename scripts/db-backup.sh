#!/bin/bash
set -uo pipefail
BACKUP_DIR=/home/ubuntu/backups/mysql
mkdir -p "$BACKUP_DIR"
FILE="$BACKUP_DIR/app-$(date +%F-%H%M).sql.gz"
mysqldump -u app -p"$(grep '^DB_PASSWORD=' /var/www/app/.env | cut -d= -f2- | tr -d '"')" \
  --single-transaction --routines --triggers app | gzip > "$FILE"
find "$BACKUP_DIR" -name "app-*.sql.gz" -mtime +7 -delete
echo "[backup] $(date) -> $FILE"
