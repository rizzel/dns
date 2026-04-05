#!/bin/bash
set -e

mysql -u root <<-EOF
    CREATE USER IF NOT EXISTS 'replicator'@'10.100.0.%' IDENTIFIED BY '${REPL_PASSWORD}';
    GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'10.100.0.%';
    FLUSH PRIVILEGES;
EOF