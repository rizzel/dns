#!/bin/bash
set -e

docker_process_sql <<-EOF
    RESET MASTER;
    SET GLOBAL gtid_slave_pos = '';
    CHANGE MASTER TO
        MASTER_HOST='10.100.0.1',
        MASTER_PORT=3306,
        MASTER_USER='replicator',
        MASTER_PASSWORD='${REPL_PASSWORD}',
        MASTER_CONNECT_RETRY=10,
        MASTER_USE_GTID=slave_pos;
    START SLAVE;
EOF
