# Bootstrapping a slave from an existing master

If the master's binlogs have been purged, the slave cannot replicate from the
beginning. In that case, bootstrap the slave with a dump from the master first.

## Prerequisites

- The master must be running (`dns-mariadb-master` container).
- The slave's MariaDB data volume should be fresh (or you're okay overwriting it).

## Steps

### 1. Dump the master database

Run this from the **master** host:

```bash
docker exec dns-mariadb-master mariadb-dump \
  -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" \
  --single-transaction --gtid --master-data=2 \
  pdns > master-dump.sql
```

### 2. Transfer the dump to the slave host

```bash
scp master-dump.sql slave-host:/path/to/dns/docker/slave/
```

Skip this if master and slave are on the same machine.

### 3. Stop replication and load the dump on the slave

```bash
# Stop replication first
docker exec dns-mariadb-slave mariadb \
  -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" \
  -e "STOP SLAVE;"

# Load the dump
docker exec -i dns-mariadb-slave mariadb \
  -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" \
  pdns < master-dump.sql

# Restart replication (picks up GTID position from the dump)
docker exec dns-mariadb-slave mariadb \
  -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" \
  -e "CHANGE MASTER TO
        MASTER_HOST='10.100.0.1',
        MASTER_PORT=3306,
        MASTER_USER='replicator',
        MASTER_PASSWORD='${REPL_PASSWORD}',
        MASTER_USE_GTID=slave_pos;
      START SLAVE;"
```

### 4. Verify replication status

```bash
docker exec dns-mariadb-slave mariadb \
  -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" \
  -e "SHOW SLAVE STATUS\G"
```

Check that `Slave_IO_Running` and `Slave_SQL_Running` are both `Yes`,
and `Seconds_Behind_Master` is `0` (or decreasing).