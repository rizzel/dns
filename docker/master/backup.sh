#!/bin/bash
#
# Database backup script for the DNS master.
#
# Creates a gzipped SQL dump and removes backups older than the retention period.
#
# Usage:
#   ./backup.sh [backup_dir]
#
# Environment:
#   MARIADB_USER      - database user (required)
#   MARIADB_PASSWORD  - database password (required)
#   MARIADB_DATABASE  - database name (default: pdns)
#   CONTAINER_NAME    - MariaDB container name (default: dns-mariadb-master)
#   RETENTION_DAYS    - days to keep backups (default: 30)
#
set -e

BACKUP_DIR="${1:-$(dirname "$0")/backups}"
CONTAINER="${CONTAINER_NAME:-dns-mariadb-master}"
DB="${MARIADB_DATABASE:-pdns}"
USER="${MARIADB_USER:?Set MARIADB_USER}"
PASS="${MARIADB_PASSWORD:?Set MARIADB_PASSWORD}"
RETENTION="${RETENTION_DAYS:-30}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
FILENAME="$BACKUP_DIR/$DB-$TIMESTAMP.sql.gz"

mkdir -p "$BACKUP_DIR"

echo "Backing up $DB to $FILENAME ..."
docker exec "$CONTAINER" mariadb-dump \
    -u"$USER" -p"$PASS" \
    --single-transaction --gtid --routines --triggers \
    "$DB" | gzip > "$FILENAME"

SIZE=$(du -h "$FILENAME" | cut -f1)
echo "Done ($SIZE)"

# Remove old backups
DELETED=$(find "$BACKUP_DIR" -name "$DB-*.sql.gz" -mtime +"$RETENTION" -delete -print | wc -l)
if [ "$DELETED" -gt 0 ]; then
    echo "Removed $DELETED backup(s) older than $RETENTION days"
fi