#!/bin/bash
set -e

docker_process_sql <<-EOF
    CREATE USER IF NOT EXISTS 'replicator'@'10.100.0.%' IDENTIFIED BY '${REPL_PASSWORD}';
    GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'10.100.0.%';
    FLUSH PRIVILEGES;
EOF
