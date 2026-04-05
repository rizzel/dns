# DNS Master Instance

This runs the master PowerDNS authoritative server with MariaDB, a PHP web interface, and a WireGuard tunnel for database replication to slave instances.

## Services

| Service    | Purpose                                              |
|------------|------------------------------------------------------|
| mariadb    | Primary database (binlog enabled for replication)    |
| wireguard  | VPN tunnel for slave replication (shares mariadb network namespace) |
| pdns       | PowerDNS authoritative server (port 53)              |
| php        | Web management interface (Apache)                    |

## Prerequisites

- Docker and Docker Compose
- The WireGuard kernel module loaded on the host (`/lib/modules` is bind-mounted)

## Setup

### 1. Create the `.env` file

```sh
cp .env.example .env
```

Edit `.env` and set **at minimum**:

- `MARIADB_PASSWORD` - password for the application database user
- `REPL_PASSWORD` - password for the replication user (must match on all slaves)

Optional settings:

- `WG_PORT` - WireGuard listen port (default: `51820`)
- `HTTP_PORT` - PHP web interface port (default: `8080`)
- `MAIL_FROM`, `USE_PEAR_MAIL`, `PEAR_*` - mail delivery settings

### 2. Configure WireGuard

```sh
cp wireguard/wg0.conf.example wireguard/wg0.conf
```

Generate a keypair:

```sh
docker run --rm --entrypoint wg linuxserver/wireguard genkey | tee wireguard/wg0.private_key | docker run --rm -i --entrypoint wg linuxserver/wireguard pubkey > wireguard/wg0.public_key
```

This saves the private key to `wireguard/wg0.private_key` (put it in the master's `wg0.conf`) and the public key to `wireguard/wg0.public_key` (give it to each slave).

Add a `[Peer]` block for each slave with its public key and WireGuard IP.

### 3. Configure `settings.php`

The PHP web interface requires a `settings.php` file. A template is provided in the repository:

```sh
cp ../../htdocs/inc/settings.php.default ../../htdocs/inc/settings.php
```

Edit `htdocs/inc/settings.php` and adjust database credentials, mail settings, etc. The PHP container will refuse to start if this file is missing.

### 4. Start the stack

```sh
docker compose up -d
```

### 5. Verify

- DNS: `dig @localhost example.com`
- Web UI: `http://<host>:<HTTP_PORT>`
- WireGuard: `docker exec dns-wireguard wg show`
- Replication user: `docker exec dns-mariadb-master mysql -u root -e "SELECT user, host FROM mysql.user WHERE user='replicator'"`

## Network Architecture

Only the mariadb container has access to the WireGuard tunnel (wireguard shares its network namespace). PowerDNS and PHP reach MariaDB over an internal Docker network. Slaves connect to the master's MariaDB on `10.100.0.1:3306` through the WireGuard tunnel.

## Exposed Ports

| Port                    | Service    |
|-------------------------|------------|
| `WG_PORT` (51820/udp)  | WireGuard  |
| 53/tcp+udp             | PowerDNS   |
| `HTTP_PORT` (8080/tcp) | PHP/Apache |