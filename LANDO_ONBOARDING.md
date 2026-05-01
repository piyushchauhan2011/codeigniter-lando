# Lando Onboarding and Troubleshooting Guide

This project uses Lando for local development.

## 1) Prerequisites

- Lando installed
- Docker installed and running
- Linux/macOS shell access

## 2) Global Lando config (optional)

With defaults, Lando serves this app at a **`.lndo.site` URL** (often **`https://my-first-lamp-app.lndo.site/`** with **no port in the address bar**). You **do not** need to edit `~/.lando/config.yml` unless you are resolving a port conflict or a team standard.

If you **do** customize the proxy, use `~/.lando/config.yml` keys such as `proxyHttpPort` and `proxyHttpsPort` (see [Lando global config](https://docs.lando.dev/config/global.html)), then run `lando poweroff` and start again.

**Rule of thumb:** whatever **`lando info`** prints under **urls** for `appserver` is what must match **`app.baseURL`** in `.env` (scheme, host, port if any, trailing slash).

**Xdebug:** uses the DBGp **client** port (Xdebug 3 default **9003**), not your HTTP/HTTPS URL. Optional PHP ini overrides belong in your own Lando/PHP setup if you need a non-default port.

## 3) Project Lando configuration

See [.lando.yml](.lando.yml). Notable points:

- **`services.appserver.scanner: false`** — avoids spurious URL probes (e.g. `127.0.0.1:8000`) on start.
- **`services.appserver.xdebug`** includes **`coverage`** so `composer test:coverage` / `test:coverage:html` work inside the container.
- **`database.healthcheck`** — quieter MySQL readiness check.

Minimal excerpt:

```yaml
scanner: false
services:
  appserver:
    xdebug: "debug,develop,coverage"
    scanner: false
  database:
    healthcheck:
      # ...
```

## 4) Start the app

```bash
cd /path/to/codeigniter-tutorial
lando start
lando info
```

Copy the **https** `appserver` URL you will actually use (or **http** if you prefer plain HTTP). Examples:

- `https://my-first-lamp-app.lndo.site/`
- `http://my-first-lamp-app.lndo.site/` (if your proxy exposes HTTP that way)

If Lando prints a **localhost** URL with a **random host port**, that is also valid—use it consistently in `.env`.

## 5) CodeIgniter environment config

Create `.env` from `env` if missing, then set **`app.baseURL`** to the **same origin** as in the browser (from `lando info`), **with a trailing slash**:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'https://my-first-lamp-app.lndo.site/'
app.forceGlobalSecureRequests = true
app.indexPage = ''

database.default.hostname = database
database.default.database = lamp
database.default.username = lamp
database.default.password = lamp
database.default.DBDriver = MySQLi
database.default.port = 3306
```

Adjust **`app.baseURL`** and **`app.forceGlobalSecureRequests`** if you use **http** instead of **https**. Wrong **`baseURL`** causes broken assets, wrong redirects, and `index.php` leaking into generated URLs.

## 6) Trust HTTPS certificate (no browser warnings)

Lando generates a local CA at `~/.lando/certs/LandoCA.crt`.

### Linux system trust

```bash
sudo cp ~/.lando/certs/LandoCA.crt /usr/local/share/ca-certificates/lando-ca.crt
sudo update-ca-certificates
```

### Chromium/Brave NSS store (if still `NET::ERR_CERT_AUTHORITY_INVALID`)

```bash
sudo apt-get install -y libnss3-tools
mkdir -p ~/.pki/nssdb
certutil -d sql:$HOME/.pki/nssdb -A -t "C,," -n "Lando Development CA" -i ~/.lando/certs/LandoCA.crt
```

If alias already exists:

```bash
certutil -d sql:$HOME/.pki/nssdb -D -n "Lando Development CA"
certutil -d sql:$HOME/.pki/nssdb -A -t "C,," -n "Lando Development CA" -i ~/.lando/certs/LandoCA.crt
```

Restart browser fully after import.

## 7) Common fixes

### A) URL works on localhost random ports, but not `lndo.site`

- Disconnect VPN/proxy temporarily
- Ensure `.lndo.site` is not proxied in shell `NO_PROXY`
- Restart with `lando poweroff && lando start`

### B) App assets are 404

- Check `.env` has correct `app.baseURL`
- Run hard refresh in browser
- Optional cache clear:

```bash
lando php spark cache:clear
```

### C) Database table not found

Run migrations:

```bash
lando php spark migrate
```

### D) “Healthcheck … FAILED … Can’t connect to MySQL” during `lando start`

MySQL binds its port a few seconds after the container boots. Early healthcheck probes can log **ERROR 2003 (connection refused)** and **mysql’s “password on the command line” warning** until the daemon is accepting connections. After a short retry loop you should still see **`✔ Healthcheck … database …`**.

This project [.lando.yml](.lando.yml) overrides the default lamp DB probe with a quieter **`mysqladmin ping -h localhost`** so logs are calmer while still waiting for readiness.

Do **not** set `healthcheck: false` unless you fully understand tooling that depends on “DB is ready” ordering.

## 8) Quick verify commands

```bash
lando info
# Replace BASE with your appserver URL from lando info (no trailing path):
curl -vkI "https://my-first-lamp-app.lndo.site/"
```
