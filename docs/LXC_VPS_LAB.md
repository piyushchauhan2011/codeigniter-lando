# LXC / VPS-style deployment lab

Hands-on layout for learning **production-shaped** PHP hosting outside Docker-only workflows: Linux containers (LXC), a **reverse proxy with TLS**, separate **PHP-FPM**, **MySQL**, optional **Redis**, and a **queue worker** supervised by systemd.

This complements:

- [LANDO_ONBOARDING.md](../LANDO_ONBOARDING.md) — local HTTPS trust patterns you can mirror with mkcert here.
- [README.md](../README.md) — tarball deploy (`scripts/deploy-digitalocean.sh`) and Docker rehearsal.

## Goals

1. Isolate services like a small VPS fleet (network boundaries, least privilege).
2. Terminate HTTPS at the proxy using certificates your workstation trusts (**mkcert**).
3. Run migrations + queue workers with clear **deploy** and **rollback** steps.
4. Practice **backup / restore** with documented **RPO** / **RTO** targets.

## Suggested topology

| Role | Runs | Notes |
|------|------|--------|
| `proxy` | nginx or Caddy | TLS termination, HTTP/2, gzip/brotli, rate limits for `/api/*` |
| `app` | php-fpm 8.2+ | CodeIgniter `public/` only mapped into doc root upstream |
| `db` | MySQL 8 | Bind to container IP; firewall DB port from WAN |
| `worker` | `php spark queue:work` | Same codebase as app; systemd Restart=always |
| `redis` (optional) | Redis 7 | Sessions or cache if you enable Redis handlers in CI4 |

Point DNS or `/etc/hosts` at the **proxy** container IP (e.g. `jobportal.local`). Never expose php-fpm directly to browsers.

### RPO / RTO (learning defaults)

- **RPO (recovery point objective):** Accept up to **24 hours** of data loss for this lab unless you automate hourly dumps (document whatever schedule you choose).
- **RTO (recovery time objective):** Restore DB + redeploy code within **1 hour** using `ops/lxc/mysql-backup.sh` + tarball rollback (`current` symlink pattern from README).

Adjust these when you simulate stricter SLAs.

## mkcert + HTTPS at the proxy

On your **workstation** (not necessarily inside CT):

```bash
mkcert -install
mkcert jobportal.local 10.0.0.42   # add IPs/hostnames that resolve to proxy CT
```

Install the resulting `jobportal.local.pem` / `jobportal.local-key.pem` on the **proxy** CT (paths referenced in [nginx-reverse-proxy-example.conf](../ops/lxc/nginx-reverse-proxy-example.conf)).

Trust story:

- **mkcert** roots your browser/OS so `https://jobportal.local` is green-bar locally.
- Match **`app.baseURL`** in `.env` to that origin (trailing slash), same discipline as Lando.

## Firewall sketch

Inside each CT (example `nftables` / `ufw`):

- Proxy: allow **443/tcp** (and **80** only if redirecting to HTTPS).
- App: allow **9000/tcp** (php-fpm) **only from proxy subnet**.
- DB: allow **3306/tcp** **only from app + worker subnets**.
- Worker: **no inbound** except SSH/admin from your LAN.

## nginx reverse proxy + rate limit

See [ops/lxc/nginx-reverse-proxy-example.conf](../ops/lxc/nginx-reverse-proxy-example.conf) for:

- `limit_req_zone` keyed by `$binary_remote_addr` for `/api/` (edge rate limit).
- FastCGI pass-through to php-fpm on the app CT.

Application-level limits remain valuable for abuse tied to routes; see API throttle filter on `/api/v1/*`.

## systemd queue worker

Example unit: [ops/lxc/codeigniter-queue-worker.service](../ops/lxc/codeigniter-queue-worker.service).

Deploy checklist reminder:

1. Put `.env` + shared `writable/` on persistent volume (same idea as tarball deploy `shared/`).
2. `php spark migrate --all`
3. `php spark cache:clear` if you use file/cache prod configs
4. `sudo systemctl restart php8.2-fpm` (version as installed)
5. `sudo systemctl restart codeigniter-queue-worker`

Rollback: rewind `current` symlink + restart workers + DB restore if migrations were incompatible.

## Backup and restore drill

**Backup:**

```bash
# From DB CT or anywhere with mysql client:
MYSQL_HOST=db MYSQL_USER=... MYSQL_PASSWORD=... MYSQL_DATABASE=... \
  ./ops/lxc/mysql-backup.sh ./backups/jobportal-$(date +%Y%m%d-%H%M).sql.gz
```

**Restore (destructive — lab only):**

```bash
gunzip -c ./backups/jobportal-YYYYMMDD-HHMM.sql.gz | mysql -h db -u user -p database
```

After restore, verify Shield users and portal smoke (`/hello`, `/jobs`, login).

## LXC hints

- Prefer **bridged networking** so CTs get stable IPs on a lab VLAN.
- Snapshot **proxy + app + db** before first HTTPS cutover.
- Keep **one Git revision** deployed everywhere; worker and app share the same release tree.

For reproducibility you can wrap CT creation in shell scripts or Ansible later; the files under `ops/lxc/` are intentionally minimal starters.
