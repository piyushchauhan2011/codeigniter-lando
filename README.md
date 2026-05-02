# CodeIgniter tutorial

Hands-on [**CodeIgniter 4**](https://codeigniter.com/) playground: MVC-style routes/controllers/views, PHP tooling (**Composer**, PHPStan, PHPUnit), and a **pnpm**/**Vite**/**TypeScript**/SCSS front end built into `public/assets/dist`. Optional **[Lando](https://lando.dev)** and **Playwright** match local and CI workflows (see [.github/workflows/ci.yml](.github/workflows/ci.yml) and [.github/workflows/playwright-lando.yml](.github/workflows/playwright-lando.yml)).

Framework reference: [**User Guide**](https://codeigniter.com/user_guide/).

## Serving the app

Point the web server (or proxy) document root at the **`public/`** directory—not the repo root—so only the front controller and static assets are web-accessible. Set `app.baseURL` in `.env` for the URL you use; with Lando-specific URLs see [LANDO_ONBOARDING.md](LANDO_ONBOARDING.md).

## Server Requirements

PHP version 8.2 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

> [!WARNING]
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - The end of life date for PHP 8.1 was December 31, 2025.
> - If you are still using a PHP version below 8.2, you should upgrade immediately.
> - The end of life date for PHP 8.2 will be December 31, 2026.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library

## Local Lando Setup

This project is set up for Lando-based local development.

- Read [LANDO_ONBOARDING.md](LANDO_ONBOARDING.md) for setup, troubleshooting, and HTTPS certificate trust steps.
- After `lando start`, use the **`https://…` appserver URL from `lando info`** (often `https://my-first-lamp-app.lndo.site/` with **no port** in the host).
- Set **`app.baseURL` in `.env`** to that same origin (trailing slash). If you see `index.php` in the URL or asset 404s, the base URL usually does not match what you opened in the browser.
- Adminer is available after `lando rebuild -y` / `lando start`; use a URL from `lando info` such as `http://adminer.my-first-lamp-app.lndo.site:8080/`, with server `database`, username `lamp`, password `lamp`, database `lamp`.

## Running CodeIgniter Tests

Information on running the CodeIgniter test suite can be found in the [README.md](tests/README.md) file in the tests directory.

## Local Quality Commands

- Install PHP dependencies: `composer install`
- Lint PHP syntax: `composer ci:php:lint`
- Static analysis: `composer phpstan:check`
- Unit tests: `composer test`

## Job Portal Auth With Shield

The job portal uses [CodeIgniter Shield](https://github.com/codeigniter4/shield) for session login, remember-me, email verification, and authorization groups.

- Demo accounts after seeding: `employer@example.test`, `seeker@example.test`, and `admin@example.test`; password for all three is `password123`.
- Groups map directly to portal areas: `employer` can manage jobs, `seeker` can apply/save jobs, and `admin` can open `/admin`.
- Local email verification uses `App\Auth\LearningEmailActivator`: the verification code appears on the verification page and is also written to the CodeIgniter log, so no SMTP server is needed for learning.
- Run migrations with `--all` so Shield package tables are created together with app tables: `lando php spark migrate --all`.
- Fresh local setup: `lando php spark migrate --all && lando php spark db:seed JobPortalDemoSeeder`.
- If your local learning database already ran the old `portal_users` migrations, use the full Shield portal reset in [LANDO_ONBOARDING.md](LANDO_ONBOARDING.md) before seeding so the new Shield foreign keys are created cleanly.

## Frontend Tooling (pnpm + Vite + TS + SCSS)

- Install frontend dependencies: `pnpm install`
- Watch & rebuild **TypeScript + SCSS** into `public/assets/dist` with **JS and `.scss` source maps**: `pnpm watch` (uses `VITE_FULL_CSS_MAP=1`; Lando + Chrome DevTools–friendly).
- Same watch **without** Sass `.map` files (slightly less work per save): `pnpm watch:fast`
- One-off production-style bundle: `pnpm build`
- One-off bundle **with** `.css.map` for SCSS debugging: `pnpm build:cssmap`
- Typecheck: `pnpm typecheck`
- Format check (oxfmt): `pnpm format:check`
- Lint (oxlint): `pnpm lint`
- Unit tests (Vitest): `pnpm test`
- Integration smoke tests (Playwright): `pnpm test:e2e`

The tutorial layout loads built assets from `public/assets/dist` (run `pnpm build` so CSS/JS exist before E2E or deploy).

### Playwright with Lando (recommended locally)

Playwright defaults to starting `php spark serve` only when **`PLAYWRIGHT_BASE_URL` is not set**. If you use Lando, start the stack first and point tests at its URL instead:

1. `lando start` (or `lando restart` after tooling / proxy changes — see `lando --help`).
2. `pnpm build` (so `public/assets/dist` exists inside the synced project).

Then either set the base URL explicitly or use these shortcuts (they assume the default **`.lndo.site`** host from [.lando.yml](.lando.yml) `name:`; if your `lando info` URLs use a non-default port, set `PLAYWRIGHT_BASE_URL` yourself):

- HTTP: `pnpm test:e2e:lando:http`
- HTTPS (sets `PLAYWRIGHT_IGNORE_HTTPS_ERRORS=1` for the local Lando cert): `pnpm test:e2e:lando:https`

Equivalent manual run:

```bash
PLAYWRIGHT_BASE_URL=http://my-first-lamp-app.lndo.site pnpm test:e2e
PLAYWRIGHT_BASE_URL=https://my-first-lamp-app.lndo.site PLAYWRIGHT_IGNORE_HTTPS_ERRORS=1 pnpm test:e2e
```

On GitHub, **browser E2E** runs in [.github/workflows/playwright-lando.yml](.github/workflows/playwright-lando.yml) via [Lando](https://docs.lando.dev/install/gha.html) (`lando/setup-lando`). The lighter [.github/workflows/ci.yml](.github/workflows/ci.yml) job runs PHP + Node checks (Vitest/build) **without** Playwright.

## CI and Release Flow

- Pull requests and `main`: **CI** workflow — PHP (lint, PHPStan, PHPUnit) and frontend (typecheck, format, oxlint, Vitest, Vite build) using `pnpm install --frozen-lockfile`.
- Same triggers: **Playwright (Lando)** workflow — starts the stack from [.lando.yml](.lando.yml), waits for `/hello`, then runs Playwright against the resolve URL (HTTP preferred; HTTPS uses `PLAYWRIGHT_IGNORE_HTTPS_ERRORS=1` when needed).
- Pushing a tag matching `v*` triggers release packaging and publishes a downloadable tarball asset.

Release a version:

1. `git tag v1.0.0`
2. `git push origin v1.0.0`

After the workflow completes you get `codeigniter-tutorial-<tag>.tar.gz`: production `composer install`, built `public/assets/dist`, Composer `vendor/`, skipped empty `writable/*` payloads (fresh logs/cache on each install).

Optional helper to download/unpack locally (no deploy wiring):

```bash
./scripts/download-release.sh OWNER/REPO v1.0.0
```

### Deploy tarball to a VPS (releases + symlink + rollback)

[`scripts/deploy-digitalocean.sh`](scripts/deploy-digitalocean.sh) SSHes into Ubuntu-style hosts (DigitalOcean Droplets count) using a rolling release layout beneath `DEPLOY_APP_ROOT`:

- `releases/<nano-timestamp>_…/` — unpacked copies (Apache never points here directly).
- `current` symlink — rewired on each deploy. Set Apache **`DocumentRoot` to `$DEPLOY_APP_ROOT/current/public`** and allow **`FollowSymLinks`** (or symlink-safe equivalent) inside that tree.
- `shared/.env`, `shared/writable/` — never copied from tarballs (release trees symlink `.env` + writable into shared state).

Exports before running locally:

```bash
export DEPLOY_SSH_TARGET=deploy@YOUR_DROPLET
export DEPLOY_APP_ROOT=/var/www/codeigniter-tutorial
```

Optional SSH config file (non-default port or identity path): `export DEPLOY_SSH_CONFIG=/abs/path/to/ssh_config`

### Local Docker rehearsal (SSH + Apache like a tiny VPS)

[`docker/deploy-local/docker-compose.yml`](docker/deploy-local/docker-compose.yml) runs a **`deploy-target`** container (PHP 8.2 + Apache + OpenSSH). From the repo root:

```bash
./scripts/deploy-local-up.sh   # generates docker/deploy-local/.ssh/, shared.env, ssh_config; compose up
```

If **`init-shared`** fails with **`Connection reset`** right after startup, **`sshd`** may still be booting — run **`make deploy-local-wait-ssh`** (or **`pnpm deploy:local:wait`**), or use **`make deploy-local-demo`**, which waits automatically.

One-shot rehearsal using the published **`v0.0.1`** artifact (downloads tarball, then deploys via SCP — same layout as DigitalOcean):

```bash
make deploy-local-demo
# equivalent: pnpm deploy:local:demo
```

Override URL/path for another release:  
`make deploy-local-demo DEPLOY_LOCAL_RELEASE_URL='https://github.com/OWNER/REPO/releases/download/vX.Y.Z/codeigniter-tutorial-vX.Y.Z.tar.gz' DEPLOY_LOCAL_ARTIFACT=dist/releases/codeigniter-tutorial-vX.Y.Z.tar.gz`

Open **`http://127.0.0.1:9080/hello`** after a successful deploy.

**Unstyled `/hello` (CSS/JS 404)?** Older packaged releases omitted `public/assets/dist/**` because [`scripts/package-release.sh`](scripts/package-release.sh) used `rsync --exclude dist`, which excluded **every** directory named `dist`, including **`public/assets/dist`**. Tags built after that fix include assets. Deploy from your workspace instead:

```bash
make deploy-local-demo-built
# equivalent: pnpm deploy:local:demo:built
```

That uses **pnpm only** (frozen lockfile). To mirror **[`.github/workflows/release.yml`](.github/workflows/release.yml)** locally (`composer install --no-dev`, `pnpm install --no-frozen-lockfile`, `pnpm build`, checks, [`scripts/package-release.sh`](scripts/package-release.sh)), then deploy into Docker:

```bash
make deploy-local-demo-ci
# equivalent: pnpm deploy:local:demo:ci
```

Tarball defaults to **`dist/codeigniter-tutorial-local-release.tar.gz`**. Override suffix:  
`make deploy-local-demo-ci RELEASE_ARTIFACT_TAG=v0.0.9-local`

Tarball **only** (no Docker): `./scripts/build-release-artifact-local.sh my-tag` or `RELEASE_ARTIFACT_TAG=my-tag pnpm deploy:local:build:release-ci`.

**Manual equivalent** (compose up first, then local tarball):

```bash
export DEPLOY_SSH_TARGET=deploy@codeigniter-local-deploy
export DEPLOY_SSH_CONFIG="$(pwd)/docker/deploy-local/ssh_config"
export DEPLOY_APP_ROOT=/srv/codeigniter-tutorial
export DEPLOY_APACHE_CMD='sudo apachectl graceful'
export DEPLOY_SMOKE_URL=http://127.0.0.1/hello   # curl runs inside the container (Apache listens on :80 there)

./scripts/deploy-digitalocean.sh init-shared
docker compose -f docker/deploy-local/docker-compose.yml exec -u root deploy-target \
  chown -R www-data:www-data /srv/codeigniter-tutorial/shared/writable

./scripts/package-release.sh dev-local
./scripts/deploy-digitalocean.sh deploy ./dist/codeigniter-tutorial-dev-local.tar.gz --label local
make deploy-local-migrate
```

The **`docker/deploy-local`** Compose stack starts **MySQL** together with PHP (`hostname` **`mysql`** inside Docker; **`127.0.0.1:3307`** on your machine for `mysql` CLI / GUI tools). [`docker/deploy-local/shared.env.example`](docker/deploy-local/shared.env.example) wires **`database.default.*`** for that database.

After any **`deploy`** into the container, run **`make deploy-local-migrate`** (or **`pnpm deploy:local:migrate`**) once so portal tables exist — otherwise **`/jobs`** reports a database connection or schema error.

[`docker/deploy-local/shared.env`](docker/deploy-local/shared.env) sets **`app.indexPage`** empty so links use **`/contact`** instead of **`/index.php/contact`** while Apache still rewrites requests to **`public/index.php`** (see **`App::$indexPage`** in [`app/Config/App.php`](app/Config/App.php)).

Teardown: [`scripts/deploy-local-down.sh`](scripts/deploy-local-down.sh) or `docker compose -f docker/deploy-local/docker-compose.yml down`

One-time filesystem prep on the Droplet:

```bash
./scripts/deploy-digitalocean.sh init-shared
scp ./production.env "${DEPLOY_SSH_TARGET}:${DEPLOY_APP_ROOT}/shared/.env"
sudo chown -R www-data:www-data "${DEPLOY_APP_ROOT}/shared/writable"
```

Adjust `sudo chown`/user to match whoever runs PHP/Apache on your Droplet (often `www-data` on Debian/Ubuntu).

When **`mod_rewrite`** maps requests to **`public/index.php`** (normal Apache setup), put **`app.indexPage =`** (empty) in **`shared/.env`** so generated links use **`/contact`** instead of **`/index.php/contact`**.

Push a tagged release (`v*`) → download URL from GitHub Releases.

```bash
./scripts/deploy-digitalocean.sh deploy 'https://github.com/OWNER/REPO/releases/download/v1.0.0/codeigniter-tutorial-v1.0.0.tar.gz'

# Roll back symlink + reload Apache to the lexical previous release bucket:
./scripts/deploy-digitalocean.sh rollback

# Inspect symlink + directories:
./scripts/deploy-digitalocean.sh releases
```

Environment knobs:

| Variable | Purpose |
| --- | --- |
| `DEPLOY_SSH_CONFIG` | Optional `-F` path for **ssh**(1) / **scp**(1) (non-default port, identity file). |
| `DEPLOY_KEEP_RELEASES` | Default **5**. Oldest dormant folders prune after each successful deploy. |
| `DEPLOY_APACHE` | `false` skips reload scripts (manual restarts instead). |
| `DEPLOY_APACHE_CMD` | Override auto-detected graceful reload commands. Requires passwordless `sudo`. |
| `DEPLOY_SMOKE_URL` | Optional `curl`; failure triggers symlink rollback automatically. |

Notes:

- Releases require GNU `date … %N`; Ubuntu images satisfy this. Fallback uses extra entropy only if `%N` is absent.
- After a failed curl smoke probe the broken release stays on-disk for forensic diffing.
