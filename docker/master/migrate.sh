#!/bin/bash
#
# Migration script: old PowerDNS setup -> new Docker setup
#
# Prerequisites:
#   - The new Docker stack is running with a fresh database (init scripts have run)
#   - The old database is accessible (adjust OLD_* variables below)
#
# Usage:
#   ./migrate.sh
#
set -e

# Old database connection
OLD_HOST="${OLD_DB_HOST:?Set OLD_DB_HOST}"
OLD_PORT="${OLD_DB_PORT:-3306}"
OLD_USER="${OLD_DB_USER:?Set OLD_DB_USER}"
OLD_PASS="${OLD_DB_PASS:?Set OLD_DB_PASS}"
OLD_DB="${OLD_DB_NAME:-pdns}"

# New database (Docker container)
NEW_CONTAINER="${NEW_CONTAINER:-dns-mariadb-master}"
NEW_USER="${MARIADB_USER:?Set MARIADB_USER}"
NEW_PASS="${MARIADB_PASSWORD:?Set MARIADB_PASSWORD}"
NEW_DB="${MARIADB_DATABASE:-pdns}"

OLD="mariadb -h$OLD_HOST -P$OLD_PORT -u$OLD_USER -p$OLD_PASS $OLD_DB"
NEW="docker exec -i $NEW_CONTAINER mariadb -u$NEW_USER -p$NEW_PASS $NEW_DB"

echo "=== Step 1: Check old salt column widths ==="
MAX_SALT=$($OLD -sN -e "SELECT MAX(LENGTH(salt)) FROM dns_users")
echo "Max salt length in old DB: $MAX_SALT"
if [ "$MAX_SALT" -gt 12 ] 2>/dev/null; then
    echo "WARNING: Old salts are up to $MAX_SALT chars, new schema allows 12."
    echo "Legacy password verification will still work (checked at login time),"
    echo "but truncated salts will prevent legacy login. Users will need to reset."
    read -p "Continue? [y/N] " -r
    [[ $REPLY =~ ^[Yy]$ ]] || exit 1
fi

echo ""
echo "=== Step 2: Disable FK checks and truncate target tables ==="
$NEW -e "
    SET FOREIGN_KEY_CHECKS = 0;
    DELETE FROM dns_records;
    DELETE FROM dns_records_users;
    DELETE FROM dns_login_attempts;
    DELETE FROM dns_users_update;
    DELETE FROM dns_users WHERE username != 'anonymous';
    DELETE FROM records;
    DELETE FROM domains;
    SET FOREIGN_KEY_CHECKS = 1;
"

echo ""
echo "=== Step 3: Migrate PowerDNS tables ==="
echo "Migrating domains..."
$OLD -sN -e "SELECT id, name, IFNULL(master,''), IFNULL(last_check,0), type, IFNULL(notified_serial,0), IFNULL(account,''), IFNULL(options,''), IFNULL(catalog,'') FROM domains" \
    | $NEW --local-infile -e "LOAD DATA LOCAL INFILE '/dev/stdin' INTO TABLE domains"

echo "Migrating records (without change_date)..."
$OLD -sN -e "SELECT id, domain_id, name, type, content, ttl, prio, disabled, ordername, auth FROM records" \
    | $NEW --local-infile -e "LOAD DATA LOCAL INFILE '/dev/stdin' INTO TABLE records"

echo ""
echo "=== Step 4: Migrate app tables ==="
echo "Migrating dns_users..."
$OLD -sN -e "SELECT username, level, password, SUBSTRING(salt, 1, 12), IFNULL(sessionid,''), email, IFNULL(locale,'en_US') FROM dns_users WHERE username != 'anonymous'" \
    | $NEW --local-infile -e "LOAD DATA LOCAL INFILE '/dev/stdin' INTO TABLE dns_users"

echo "Migrating dns_users_update..."
$OLD -sN -e "SELECT * FROM dns_users_update" \
    | $NEW --local-infile -e "LOAD DATA LOCAL INFILE '/dev/stdin' INTO TABLE dns_users_update"

echo "Migrating dns_records_users..."
$OLD -sN -e "SELECT * FROM dns_records_users" \
    | $NEW --local-infile -e "LOAD DATA LOCAL INFILE '/dev/stdin' INTO TABLE dns_records_users"

echo ""
echo "=== Step 5: Populate dns_records from old change_date ==="
HAS_CHANGE_DATE=$($OLD -sN -e "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$OLD_DB' AND TABLE_NAME='records' AND COLUMN_NAME='change_date'")
if [ "$HAS_CHANGE_DATE" -gt 0 ]; then
    echo "Migrating change_date to dns_records..."
    $OLD -sN -e "SELECT id, FROM_UNIXTIME(change_date) FROM records WHERE change_date IS NOT NULL AND change_date > 0" \
        | $NEW --local-infile -e "LOAD DATA LOCAL INFILE '/dev/stdin' INTO TABLE dns_records (records_id, change_date)"
    echo "Done."
else
    echo "No change_date column found in old records table, skipping."
fi

echo ""
echo "=== Step 6: Reset auto-increment counters ==="
$NEW -e "
    SELECT SETVAL(domains_id_seq, (SELECT COALESCE(MAX(id),0) FROM domains));
    SELECT SETVAL(records_id_seq, (SELECT COALESCE(MAX(id),0) FROM records));
" 2>/dev/null || {
    # InnoDB auto-increment resets automatically on restart, but let's be safe
    MAX_DOMAIN_ID=$($NEW -sN -e "SELECT COALESCE(MAX(id),0)+1 FROM domains")
    MAX_RECORD_ID=$($NEW -sN -e "SELECT COALESCE(MAX(id),0)+1 FROM records")
    $NEW -e "ALTER TABLE domains AUTO_INCREMENT = $MAX_DOMAIN_ID"
    $NEW -e "ALTER TABLE records AUTO_INCREMENT = $MAX_RECORD_ID"
}

echo ""
echo "=== Migration complete ==="
echo ""
echo "Post-migration checklist:"
echo "  - Verify record count: old vs new"
echo "  - Test DNS resolution"
echo "  - Test web login"
echo "  - If SOA records are missing timing fields, update them via the admin panel"