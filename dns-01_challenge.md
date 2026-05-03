# DNS-01 challenge

I want to include an API endpoint to enable DNS-01 let's encrypt challenges. For this, every user that owns a domain should be able to access a special endpoint to set the `_acme-challenge.<YOUR-DOMAIN>` TXT record for that domain. This includes A, AAAA and CNAME records.

# Cleanup

Best would probably be to automatically remove the TXT record after a specific amount of time. Maybe 30 minutes. Another way would be to have a second endpoint to remove the TXT record again.

When calling the set endpoint a second time for the same domain, this will overwrite the first TXT record.

---

# Implementation plan

## Scope

In scope:

- Set a `_acme-challenge.<name>` TXT record where `<name>` is a domain name the caller already owns an A, AAAA, or CNAME record for (exact-name match).
- Clear that TXT record explicitly.
- Lazy auto-expiry of stale ACME TXT records after 30 minutes.

Out of scope (deliberate, for now):

- Wildcard certificates (would require setting `_acme-challenge.<apex>` without owning an A/AAAA/CNAME at the apex).
- Cron- or event-scheduler-driven cleanup. Containers only run Apache today, so cleanup is performed lazily inside the request path. If a true scheduler is added later, the same cleanup query can be lifted into it.

## API surface

Two URL families, both authenticated by the per-record update password from `dns_records_users` — same model as `/ip4` / `/ip6`. No login session, no CSRF (these endpoints are called by ACME clients, not browsers).

### Query-string style (matches existing `/ip4`)

```
GET /acme-set?<recordname>;<password>;<token>
GET /acme-clear?<recordname>;<password>
```

- `<recordname>` is the *base* name (e.g. `home.example.com`). The handler prepends `_acme-challenge.` itself — this prevents callers from injecting other underscore-prefixed names.
- `<token>` is the raw ACME challenge value (RFC 8555 §8.4). Stored unquoted; PowerDNS quotes on the wire.

### JSON POST style (`/acme.php`)

```
POST /acme.php
Content-Type: application/json

{ "action": "set",   "name": "home.example.com", "password": "...", "token": "..." }
{ "action": "clear", "name": "home.example.com", "password": "..." }
```

Same auth, same validation, same rate limiting. Returns `200 OK` on success, `400`/`403`/`404` on failure (no body needed; matching the existing endpoints' minimalism).

### Webserver rewrites

Add `acme-set` and `acme-clear` to the existing alternation in both `apacheconf/dns.include` and `lighttpdconf/40-dns.conf`. `/acme.php` needs no rewrite (it resolves directly). `/acme.php` must also be added to the lighttpd HTTP→HTTPS bypass list so ACME clients on plain HTTP can reach it (mirroring `/update.php`).

## Authentication & authorization

- Look up `<recordname>` in `records` joined to `dns_records_users` where `type IN ('A','AAAA','CNAME')` and `password = <password>` and `LENGTH(password) > 0`.
- A match means the caller proved ownership of an A/AAAA/CNAME at exactly `<recordname>`.
- The challenge TXT is then written at `_acme-challenge.<recordname>` under the same `domain_id` as the matched record.
- No match → record a row in `dns_login_attempts` (reuses the existing 10-fails-per-15-min-per-IP limiter from `recordUpdateIPx`) and return failure.

## Validation

- `<recordname>` must pass `Domains::isValidDomainName`.
- `<token>` must be 1–255 characters of printable ASCII (`/^[\x21-\x7E]+$/`).
  - 255 is the per-character-string TXT limit from RFC 1035 §3.3.14. ACME tokens (RFC 8555 §8.1) are base64url-encoded SHA-256 → exactly 43 chars, so 255 is generous headroom without ever needing TXT-string concatenation.
- `set` and `clear` are idempotent: `clear` returns success even if the record wasn't present.

## Cleanup

### Manual

`/acme-clear` (or `{"action":"clear"}`) deletes the matching TXT row, bumps SOA.

### Lazy auto-expiry

At the *start* of each `/acme-set` and `/acme-clear` request, run one cleanup query: delete every `records` row where `type='TXT' AND name LIKE '\_acme-challenge.%' AND id IN (SELECT records_id FROM dns_records WHERE change_date < NOW() - INTERVAL 30 MINUTE)`. Collect distinct `domain_id`s of deleted rows beforehand and bump their SOA serials. Cost: one indexed delete per ACME call — trivial.

Cascade: ACME TXT rows are not registered in `dns_records_users`. If the underlying A/AAAA/CNAME is deleted via the existing `deleteRecord`, the ACME TXT for that name is *not* automatically removed — but it'll either be overwritten by the next ACME run or expire within 30 minutes via the lazy sweep. Acceptable.

## Rate limiting

Reuse `dns_login_attempts` exactly like `recordUpdateIPx`: 10 failures per IP per 15 minutes. On success, clear that IP's failed attempts.

## Database

No schema changes. Re-uses:

- `records` — the TXT row itself (`type='TXT'`, `name='_acme-challenge.<recordname>'`, `content=<token>`, `ttl=60`).
- `dns_records` — gives us `change_date` for lazy expiry. `touchRecord()` already updates this on insert/update.
- `dns_records_users` — read-only, for ownership lookup of the underlying A/AAAA/CNAME. ACME TXT records are *not* inserted here.
- `dns_login_attempts` — rate limiter.

TTL for the ACME TXT row: 60s (the record is transient and resolvers shouldn't cache it).

## Code touch list

1. **`htdocs/inc/domains.php`** — three new methods on `Domains`:
   - `setAcmeChallenge(string $recordName, string $password, string $token): bool`
   - `clearAcmeChallenge(string $recordName, string $password): bool`
   - `private expireAcmeChallenges(): void` — the lazy sweep, called at the top of the two public methods.

   These bypass the `addRecord` type-whitelist (which restricts to A/AAAA/CNAME) by writing to `records` directly, scoped to TXT + `_acme-challenge.` prefix.

2. **`htdocs/rpc.php`** — two new cases in the `switch ($name)` block: `/acme-set` and `/acme-clear`, mirroring the `/ip4` shape.

3. **`htdocs/acme.php`** — new front-controller for the JSON POST flavor: parse body, dispatch to the same two `Domains` methods, return appropriate status code.

4. **`apacheconf/dns.include`** — extend the rewrite alternation: `^/(ip4|ip6|ip|myip|inadyn4|inadyn6|acme-set|acme-clear)\b…`.

5. **`lighttpdconf/40-dns.conf`** — same alternation in `url.rewrite-once`, plus add `/acme.php` and the two rewrites to the HTTP-bypass list so ACME-over-HTTP works.

6. **`README.md`** — short section under "Dynamic IP Update API" documenting the new endpoints.
