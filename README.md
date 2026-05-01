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

## Running CodeIgniter Tests

Information on running the CodeIgniter test suite can be found in the [README.md](tests/README.md) file in the tests directory.

## Local Quality Commands

- Install PHP dependencies: `composer install`
- Lint PHP syntax: `composer ci:php:lint`
- Static analysis: `composer phpstan:check`
- Unit tests: `composer test`

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

One-time filesystem prep on the Droplet:

```bash
./scripts/deploy-digitalocean.sh init-shared
scp ./production.env "${DEPLOY_SSH_TARGET}:${DEPLOY_APP_ROOT}/shared/.env"
sudo chown -R www-data:www-data "${DEPLOY_APP_ROOT}/shared/writable"
```

Adjust `sudo chown`/user to match whoever runs PHP/Apache on your Droplet (often `www-data` on Debian/Ubuntu).

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
| `DEPLOY_KEEP_RELEASES` | Default **5**. Oldest dormant folders prune after each successful deploy. |
| `DEPLOY_APACHE` | `false` skips reload scripts (manual restarts instead). |
| `DEPLOY_APACHE_CMD` | Override auto-detected graceful reload commands. Requires passwordless `sudo`. |
| `DEPLOY_SMOKE_URL` | Optional `curl`; failure triggers symlink rollback automatically. |

Notes:

- Releases require GNU `date … %N`; Ubuntu images satisfy this. Fallback uses extra entropy only if `%N` is absent.
- After a failed curl smoke probe the broken release stays on-disk for forensic diffing.
