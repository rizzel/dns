#!/bin/bash
set -e

mysql -u root <<-EOF
    SET GLOBAL server_id = ${SERVER_ID:-2};
    CHANGE MASTER TO
        MASTER_HOST='10.100.0.1',
        MASTER_PORT=3306,
        MASTER_USER='replicator',
        MASTER_PASSWORD='${REPL_PASSWORD}',
        MASTER_USE_GTID=slave_pos;
    START SLAVE;
EOF