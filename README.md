# Dynamic DNS Server

A dynamic DNS server with a PHP web interface and PowerDNS as the authoritative DNS backend. Supports master-slave replication over WireGuard.

## Features

- Creation of multiple DNS records (A, AAAA, CNAME) per user
- Separate update passwords for each record
- Automatic reverse DNS (PTR) records
- HTTP API for dynamic IP updates (compatible with inadyn and similar clients)
- Master-slave replication via MariaDB over WireGuard
- Multi-language support (i18n via gettext)

## Architecture

- **PHP** web interface for managing domains, records, and users
- **PowerDNS** authoritative DNS server with a MariaDB backend
- **MariaDB** for storing DNS zones, records, and application data
- **WireGuard** VPN for secure master-slave replication

## Deployment

The project is deployed via Docker Compose.

### Master

See [`docker/master/`](docker/master/) for the master setup. Copy `.env.example` to `.env` and adjust the values, then:

```bash
cd docker/master
cp .env.example .env
# Edit .env with your settings
docker compose up -d
```

An initial admin user and password will be printed to the container logs on first startup:

```bash
docker logs dns-php 2>&1 | grep -A5 "admin user created"
```

### Slave

See [`docker/slave/`](docker/slave/) for the slave setup. Each slave replicates the master's database over WireGuard and runs its own PowerDNS instance.

```bash
cd docker/slave
cp .env.example .env
# Edit .env with your settings
docker compose up -d
```

Before starting, ensure:
- The `REPL_PASSWORD` matches the master's
- The `server-id` in `mariadb/slave.cnf` is unique per slave
- The WireGuard configs are set up for both peers

For bootstrapping a slave from an existing master (when binlogs have been purged), see [`docker/slave/BOOTSTRAP.md`](docker/slave/BOOTSTRAP.md).

## Dynamic IP Update API

Records can be updated via HTTP without logging in, using the record's update password:

```
GET /ip?<record_id>;<password>
GET /ip4?<record_name>;<password>
GET /ip6?<record_name>;<password>
```

The client's IP is auto-detected. To specify an IP explicitly, append it as a third parameter:

```
GET /ip4?<record_name>;<password>;<ip_address>
```

## Configuration

The application configuration is in `htdocs/inc/settings.php`. The Docker setup populates this from environment variables defined in `.env`.