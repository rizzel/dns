#!/bin/bash
set -e

SETTINGS_FILE="/var/www/html/htdocs/inc/settings.php"

if [ ! -f "$SETTINGS_FILE" ]; then
    echo "ERROR: $SETTINGS_FILE not found." >&2
    echo "Copy settings.php.default to settings.php and adjust it before starting." >&2
    exit 1
fi

exec "$@"