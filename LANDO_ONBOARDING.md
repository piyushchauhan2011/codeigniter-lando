# Lando Onboarding and Troubleshooting Guide

This project uses Lando for local development.

## 1) Prerequisites

- Lando installed
- Docker installed and running
- Linux/macOS shell access

## 2) Global Lando proxy port setup (important)

Set your global Lando proxy ports so this project uses `8080` and `8443`:

`~/.lando/config.yml`

```yaml
proxyHttpPort: 8080
proxyHttpsPort: 8443
```

Then apply:

```bash
lando poweroff
```

## 3) Project Lando configuration

This project uses:

```yaml
name: my-first-lamp-app
recipe: lamp
config:
  webroot: public
  php: '8.2'
scanner: false
```

`scanner: false` is used to avoid noisy URL probe errors for `127.0.0.1:8000` and `127.0.0.1:443`.

## 4) Start the app

```bash
cd /path/to/codeigniter-tutorial
lando start
lando info
```

Expected main URLs:

- `http://my-first-lamp-app.lndo.site:8080/`
- `https://my-first-lamp-app.lndo.site:8443/`

## 5) CodeIgniter environment config

Create `.env` from `env` if missing, then set:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'https://my-first-lamp-app.lndo.site:8443/'
app.forceGlobalSecureRequests = true
app.indexPage = ''

database.default.hostname = database
database.default.database = lamp
database.default.username = lamp
database.default.password = lamp
database.default.DBDriver = MySQLi
database.default.port = 3306
```

These values fix:

- wrong asset/debugbar URLs pointing to `localhost:8080`
- `index.php` appearing in generated URLs

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
curl -vkI https://my-first-lamp-app.lndo.site:8443/
```
