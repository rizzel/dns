#!/bin/bash
set -e

SETTINGS_FILE="/var/www/html/htdocs/inc/settings.php"

if [ ! -f "$SETTINGS_FILE" ]; then
    echo "ERROR: $SETTINGS_FILE not found." >&2
    echo "Copy settings.php.default to settings.php and adjust it before starting." >&2
    exit 1
fi

# Create initial admin user if it doesn't exist yet
php -r '
    require_once("/var/www/html/htdocs/inc/settings.php");
    $settings = require("/var/www/html/htdocs/inc/settings.php");
    $db = $settings["db"];
    $dsn = sprintf("mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4", $db["host"], $db["port"], $db["name"]);
    $pdo = new PDO($dsn, $db["user"], $db["pass"]);

    $username = getenv("DNS_ADMIN_USER") ?: "admin";
    $email = getenv("DNS_ADMIN_EMAIL") ?: "admin@localhost";

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM dns_users WHERE username = ?");
    $stmt->execute([$username]);
    if ((int) $stmt->fetchColumn() > 0) {
        exit(0);
    }

    $password = bin2hex(random_bytes(16));
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO dns_users (username, password, salt, level, email, locale) VALUES (?, ?, \"\", \"admin\", ?, \"en_US\")");
    $stmt->execute([$username, $hash, $email]);

    echo "\n";
    echo "========================================\n";
    echo "  Initial admin user created\n";
    echo "  Username: " . $username . "\n";
    echo "  Password: " . $password . "\n";
    echo "========================================\n";
    echo "\n";
'

# Disable opcache in debug mode
if [ "${DEBUG:-0}" = "1" ] || [ "${DEBUG:-0}" = "true" ]; then
    echo "opcache.enable=0" > /usr/local/etc/php/conf.d/99-debug.ini
fi

exec "$@"