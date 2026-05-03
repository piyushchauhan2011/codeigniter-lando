# GlitchTip with Lando (browser errors)

This project runs **[GlitchTip](https://glitchtip.com)** beside the Elastic stack. GlitchTip accepts **Sentry-compatible** browser SDK traffic and optional **release/source map** uploads.

## HTTPS portal → ingest tunnel (default)

When the job portal is served over **`https://my-first-lamp-app…`** but GlitchTip is only reachable internally or over HTTP, browsers block **mixed-content** POSTs straight to GlitchTip.

This repo defaults to the [Sentry **`tunnel`](https://docs.sentry.io/platforms/javascript/troubleshooting/#using-the-tunnel-option)** pattern:

1. **`@sentry/browser`** POSTs envelopes to **`POST /learning/elk/glitchtip-tunnel`** on the **same origin** as the portal (HTTPS OK).
2. **[`GlitchTipTunnel`](../app/Controllers/GlitchTipTunnel.php)** validates the envelope header and forwards the raw body to **`{glitchtip.internalIngestBase}/api/{project_id}/envelope/`** — by default **`http://glitchtip:8000`** on the Lando Docker network ([`GlitchTip` config](../app/Config/GlitchTip.php)).

You still set **`VITE_GLITCHTIP_DSN`** from GlitchTip **Client Keys** so the SDK builds correct payloads and envelope headers.

To **disable** tunneling (browser talks to GlitchTip hosts directly), set **`VITE_GLITCHTIP_TUNNEL_PATH=`** (empty) and **`pnpm build`**.

## URLs

After **`lando rebuild -y`** (needed the first time GlitchTip services are added):

- **GlitchTip UI:** **`https://glitchtip-my-first-lamp-app.lndo.site/login`** or **`http://glitchtip-my-first-lamp-app.lndo.site:8080/login`** if HTTPS misbehaves on your Docker/Lando combo.
- **`http://glitchtip-my-first-lamp-app.lndo.site:8000/`** — same container, alternate Lando binding.
- **`GLITCHTIP_DOMAIN`** in **[`.lando.yml`](../.lando.yml)** drives GlitchTip’s own links and **Client Keys** DSN host — it does **not** need to match how envelopes reach GlitchTip when you use the tunnel.
- Print reminders: **`lando glitchtip-url`**

The hostname uses a **single label** before **`.lndo.site`** so it matches Lando’s TLS wildcard when you use HTTPS.

## First-time setup

1. Open the UI → **Sign up** (local lab).
2. Create an **organization** and **project** (for example **codeigniter-tutorial-frontend**, **JavaScript**).
3. Copy the project **DSN** under **Client Keys** (browser).
4. Optional: create an **auth token** with permission to upload release artifacts / source maps (GlitchTip settings vary by version; use whatever your UI labels “Internal integration” / auth token for releases).

## Frontend env (`vite`)

| Variable | Purpose |
|----------|---------|
| **`VITE_GLITCHTIP_DSN`** | Required. Paste **Client Keys** DSN from GlitchTip (still required with **`tunnel`**). |
| **`VITE_GLITCHTIP_TUNNEL_PATH`** | Optional. Relative path for **`Sentry.init({ tunnel })`**. Default **`/learning/elk/glitchtip-tunnel`**. Set to **empty** to disable tunneling. |
| **`VITE_GLITCHTIP_RELEASE`** | Optional; should match **`SENTRY_RELEASE`** when uploading source maps. |
| **`VITE_GLITCHTIP_TRACES_SAMPLE_RATE`** | Optional **`0`–`1`**. Defaults: **`0`** in dev, **`0.01`** in production builds (same idea as GlitchTip’s onboarding `tracesSampleRate`). |

### Backend env (`glitchtip.*`)

Used only by **`GlitchTipTunnel`** (PHP). Defaults suit Lando:

| Variable | Purpose |
|----------|---------|
| **`glitchtip.internalIngestBase`** | GlitchTip root URL from **appserver** (no trailing slash). Default **`http://glitchtip:8000`**. |
| **`glitchtip.allowedProjectIds`** | Optional comma-separated allowlist (e.g. **`1`**). Empty = allow any numeric project id (**lab only**). |
| **`glitchtip.allowedDsnHosts`** | Optional comma-separated DSN hosts (e.g. **`glitchtip-my-first-lamp-app.lndo.site`**). Empty = skip host check (**lab only**). |

### **`GLITCHTIP_DOMAIN`** vs tunnel

With tunneling, **`GLITCHTIP_DOMAIN`** mainly affects GlitchTip’s UI links and the hostname embedded in **Client Keys**. **`glitchtip.internalIngestBase`** is where PHP forwards envelopes.

This repo already depends on **`@sentry/browser`** and initializes from [`resources/ts/glitchtip.ts`](../resources/ts/glitchtip.ts) (wired in [`portal.ts`](../resources/ts/portal.ts)) with a low **`tracesSampleRate`** in production builds. You do **not** need to run `npm install @sentry/browser` again unless your lockfile is out of date.

Example local `.env` (never commit real keys; rotate if a key was pasted into chat or logs):

```bash
VITE_GLITCHTIP_DSN=https://YOUR_KEY@glitchtip-my-first-lamp-app.lndo.site/YOUR_PROJECT_ID
# Optional lock-down when exposing the tunnel beyond localhost:
# glitchtip.allowedProjectIds=1
# glitchtip.allowedDsnHosts=glitchtip-my-first-lamp-app.lndo.site
VITE_GLITCHTIP_RELEASE=local-dev
```

Rebuild assets after changing **`VITE_*`** env:

```bash
pnpm build
```

## Source maps (`@sentry/vite-plugin` or GlitchTip CLI)

GlitchTip may suggest **`glitchtip-cli sourcemaps inject|upload`** for `./dist`. This project builds to **`public/assets/dist`** and can use **`@sentry/vite-plugin`** instead when **`SENTRY_*`** env vars are set (next section).

[`vite.config.ts`](../vite.config.ts) registers **`sentryVitePlugin`** only when **all** of these are non-empty:

- **`SENTRY_AUTH_TOKEN`** — GlitchTip auth token for uploads  
- **`SENTRY_ORG`** — organization slug  
- **`SENTRY_PROJECT`** — project slug  
- **`SENTRY_RELEASE`** — release identifier (must match what events use, via **`release.inject`** and/or **`VITE_GLITCHTIP_RELEASE`**)  
- **`SENTRY_URL`** — GlitchTip base URL with scheme (and port if not 443), e.g. **`https://glitchtip-my-first-lamp-app.lndo.site`** (no trailing path). For HTTP **:8080** labs use **`http://glitchtip-my-first-lamp-app.lndo.site:8080`**.

Example:

```bash
export SENTRY_URL=https://glitchtip-my-first-lamp-app.lndo.site
export SENTRY_ORG=my-org-slug
export SENTRY_PROJECT=my-project-slug
export SENTRY_RELEASE=local-dev
export SENTRY_AUTH_TOKEN=YOUR_TOKEN_HERE
pnpm build
```

### `pnpm approve-builds`

pnpm may block **`@sentry/cli`** install scripts until approved:

```bash
pnpm approve-builds
```

Choose **`@sentry/cli`** when prompted (team policy permitting).

## Lando stack notes

Defined in **[`.lando.yml`](../.lando.yml)**:

- **`glitchtippostgres`** — Lando **`postgres:16`** (`postgres` / `postgres`, database **`postgres`**). Prefer this over a raw `postgres` compose service so Lando manages users/volumes reliably with Docker Engine 29.x.
- **`glitchtip`** — **`glitchtip/glitchtip:6`** with **`SERVER_ROLE: all_in_one`**, **`VALKEY_URL`** empty (lighter footprint). Uses **`command: ./bin/start.sh`**, **`user: root`**, and **`LANDO_DROP_USER: app`** so the app runs as the image’s **`app`** user after Lando boot steps.

Regenerate **`SECRET_KEY`** for anything beyond local experimentation (`openssl rand -hex 32`) and move secrets out of `.lando.yml` if you fork this for shared environments.

## Troubleshooting

- **`403` on `POST …/learning/elk/glitchtip-tunnel`** — Often GlitchTip/Django rejecting the proxied request because **`Host`** must match **`GLITCHTIP_DOMAIN`**. The tunnel forwards over **`http://glitchtip:8000`** but sets **`Host`** from the envelope DSN (updated in **`GlitchTipTunnel`**). If it persists, check **`writable/logs/`** for **`GlitchTip tunnel upstream HTTP`** warnings (upstream body snippet). Also verify **`.env`** does not set **`glitchtip.allowedDsnHosts`** / **`glitchtip.allowedProjectIds`** to values that exclude your DSN unless intentional (those return **`403`** from PHP before upstream).
- **“GlitchTip not initialized — capture skipped”** in the browser — **`VITE_GLITCHTIP_DSN`** was missing or empty when **`pnpm build`** last ran. Vite only inlines **`VITE_*`** at **build** time. Add **`VITE_GLITCHTIP_DSN`** to a project-root **`.env`** (start from **`.env.example`**), then **`pnpm build`** and hard-refresh the job portal.
- **Mixed content / blocked GlitchTip request** — ensure tunneling is enabled (default): **`VITE_GLITCHTIP_TUNNEL_PATH`** must **not** be set to empty unless you intentionally bypass the proxy. **`pnpm build`** after changing **`VITE_*`**.
- **`502` / `Upstream unavailable` from **`POST /learning/elk/glitchtip-tunnel`**** — **`glitchtip.internalIngestBase`** must resolve from **appserver** (default **`http://glitchtip:8000`** on Lando). Run **`lando restart`** if services were recreated.
