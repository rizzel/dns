# DNS Slave Instance

This runs a slave PowerDNS authoritative server with a read-only MariaDB that replicates from the master over a WireGuard tunnel.

## Services

| Service    | Purpose                                              |
|------------|------------------------------------------------------|
| mariadb    | Read-only replica (replicates from master via WireGuard) |
| wireguard  | VPN tunnel to master (shares mariadb network namespace) |
| pdns       | PowerDNS authoritative server (port 53)              |

## Prerequisites

- Docker and Docker Compose
- The WireGuard kernel module loaded on the host (`/lib/modules` is bind-mounted)
- A running master instance with WireGuard configured to accept this slave as a peer

## Setup

### 1. Create the `.env` file

```sh
cp .env.example .env
```

Edit `.env` and set:

- `MARIADB_PASSWORD` - password for the application database user
- `REPL_PASSWORD` - replication password (**must match the master**)
- `SERVER_ID` - unique ID for this slave (2, 3, 4, ... - must not collide with master or other slaves)

Optional settings:

- `DNS_PORT` - PowerDNS listen port (default: `53`)
- `WG_PORT` - WireGuard listen port (default: `51821`)

### 2. Configure MariaDB

```sh
cp mariadb/slave.cnf.example mariadb/slave.cnf
```

### 3. Configure WireGuard

```sh
cp wireguard/wg0.conf.example wireguard/wg0.conf
```

Generate a keypair:

```sh
docker run --rm --entrypoint wg linuxserver/wireguard genkey | tee wireguard/wg0.private_key | docker run --rm -i --entrypoint wg linuxserver/wireguard pubkey > wireguard/wg0.public_key
```

This saves the private key to `wireguard/wg0.private_key` (put it in this slave's `wg0.conf`) and the public key to `wireguard/wg0.public_key` (add it as a `[Peer]` on the master).

Edit `wg0.conf` and set:

- `PrivateKey` - this slave's private key
- `PublicKey` - the master's public key
- `Endpoint` - the master's public IP and WireGuard port (e.g. `203.0.113.1:51820`)

### 4. Add this slave as a peer on the master

On the master, add a `[Peer]` block to `wireguard/wg0.conf`:

```ini
[Peer]
PublicKey = <this slave's public key>
AllowedIPs = 10.100.0.X/32
```

Restart the master's WireGuard container after adding the peer.

### 5. Start the stack

```sh
docker compose up -d
```

On first start, the init script (`mariadb/init/01-start-replication.sh`) automatically configures replication to the master at `10.100.0.1:3306` using GTID.

### 6. Verify

- DNS: `dig @localhost example.com`
- WireGuard: `docker exec dns-wireguard wg show`
- Replication status: `docker exec dns-mariadb-slave mysql -u root -e "SHOW SLAVE STATUS\G"`

## Network Architecture

Only the mariadb container has access to the WireGuard tunnel (wireguard shares its network namespace). PowerDNS reaches MariaDB over an internal Docker network. The slave's MariaDB connects to the master at `10.100.0.1:3306` through the WireGuard tunnel for replication.

## Exposed Ports

| Port                     | Service    |
|--------------------------|------------|
| `WG_PORT` (51821/udp)   | WireGuard  |
| `DNS_PORT` (53/tcp+udp) | PowerDNS   |