#!/bin/bash
set -e

mysql -u root -p"${MARIADB_ROOT_PASSWORD}" <<-EOF
    CREATE USER IF NOT EXISTS 'replicator'@'10.100.0.%' IDENTIFIED BY '${REPL_PASSWORD}';
    GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'10.100.0.%';
    FLUSH PRIVILEGES;
EOF