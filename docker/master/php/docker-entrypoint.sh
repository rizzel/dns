#!/bin/bash
set -e

SETTINGS_FILE="/var/www/html/htdocs/inc/settings.php"

if [ ! -f "$SETTINGS_FILE" ]; then
    cat > "$SETTINGS_FILE" <<SETTINGS
<?php

class Settings {
    public \$db = array(
        'dbHost' => "${DB_HOST:-127.0.0.1}",
        'dbPort' => ${DB_PORT:-3306},
        'dbUser' => "${DB_USER}",
        'dbPass' => "${DB_PASS}",
        'dbName' => "${DB_NAME:-pdns}"
    );

    public \$defaultScripts = array(
        'js/spin.min.js',
        'js/string.js',
        'js/md5.js',
        'js/dns.js'
    );

    public \$defaultStyles = array(
        'css/dns.css'
    );

    public \$mailFrom = '${MAIL_FROM}';
    public \$usePearMail = ${USE_PEAR_MAIL:-FALSE};
    public \$pearBackend = $([ -n "$PEAR_BACKEND" ] && echo "'$PEAR_BACKEND'" || echo "NULL");
    public \$pearConfig = array(
        'host' => '${PEAR_HOST}',
        'username' => '${PEAR_USERNAME}',
        'password' => '${PEAR_PASSWORD}',
        'auth' => '${PEAR_AUTH:-LOGIN}',
        'port' => ${PEAR_PORT:-25}
    );
}
SETTINGS
fi

exec "$@"