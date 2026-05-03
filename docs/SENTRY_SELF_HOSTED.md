# Self-hosted Sentry (browser JS)

This repo sends **portal JavaScript errors** to **Sentry**, not Elastic RUM. PHP traces stay on **Elastic APM** (see [ELK_LEARNING_LAB.md](../ELK_LEARNING_LAB.md)).

## Scope

Upstream **[getsentry/self-hosted](https://github.com/getsentry/self-hosted)** is a **multi-service** Docker stack (PostgreSQL, Redis, workers, ingestion, etc.). It is intentionally **not** inlined into [`.lando.yml`](../.lando.yml): disk/RAM and upgrade cadence belong with Sentry’s installer.

## Install Sentry

1. Clone and install per official docs:

   ```bash
   git clone https://github.com/getsentry/self-hosted.git
   cd self-hosted
   ./install.sh
   ```

2. Open the web UI (default is typically **`http://localhost:9000`** — confirm in the installer output).
3. Sign in, create an organization and a **Browser JavaScript** (or generic frontend) **project**.
4. Copy the **DSN** from **Project Settings → Client Keys**.

### Auth token for releases / source maps

Create an **internal integration** or **auth token** with at least **`project:releases`** and **`org:read`** so uploads succeed.

## Environment variables

Use a **project-local `.env`** for Vite (never commit secrets).

### Browser runtime (`import.meta.env`)

| Variable | Purpose |
|----------|---------|
| `VITE_SENTRY_DSN` | Required for the SDK to send events. Must use a host/port the **browser** can reach from your app origin. |
| `VITE_SENTRY_RELEASE` | Strongly recommended; must match **`SENTRY_RELEASE`** used when uploading maps. |
| `VITE_SENTRY_ENVIRONMENT` | Optional; defaults to Vite `MODE`. |
| `VITE_SENTRY_TRACES_SAMPLE_RATE` | Optional; default `0` (performance traces off). |

Example:

```bash
VITE_SENTRY_DSN=http://YOUR_KEY@localhost:9000/YOUR_PROJECT_ID
VITE_SENTRY_RELEASE=local-dev
VITE_SENTRY_ENVIRONMENT=development
```

### Source maps (`@sentry/vite-plugin`, enabled during `pnpm build`)

All of the following must be set **together** or the plugin stays disabled:

| Variable | Purpose |
|----------|---------|
| `SENTRY_AUTH_TOKEN` | Token with release/upload scopes. |
| `SENTRY_ORG` | Organization slug (see Sentry UI URL). |
| `SENTRY_PROJECT` | Project slug. |
| `SENTRY_RELEASE` | Same string as **`VITE_SENTRY_RELEASE`**. |
| `SENTRY_URL` | Base URL of your Sentry install (e.g. `http://localhost:9000`). |

Then:

```bash
pnpm build
```

Artifacts under `public/assets/dist/**` upload when the plugin runs; bundles keep **`sourcemap: "hidden"`** so stacks reference deployed URLs while maps live server-side.

## HTTPS and mixed content

Pick **one** approach:

1. **Lab HTTP app + HTTP Sentry**  
   Browse the CodeIgniter app over **`http://my-first-lamp-app.lndo.site:8000`** (or similar) and point `VITE_SENTRY_DSN` at **`http://localhost:9000`** (or another HTTP origin reachable from the browser). Avoids mixed-content blocking.

2. **HTTPS app + reachable HTTPS Sentry**  
   Terminate TLS for Sentry on a hostname whose certificate your browser trusts (same constraints as Kibana in ELK_LEARNING_LAB: prefer **one DNS label** before **`.lndo.site`** if you front it with Lando’s proxy).

3. **Advanced**  
   Attach the self-hosted Compose stack to the **same Docker network** as Lando’s Traefik proxy and add a **`proxy:`** route (similar to Kibana). Document any hostname you choose in team notes.

Also configure **allowed browser origins / CSRF-related settings** in Sentry so `https://my-first-lamp-app.lndo.site` (or your HTTP origin) may POST envelopes — check **Settings → Security** / relay docs for your Sentry version.

## Verify end-to-end

1. `pnpm build` with `VITE_SENTRY_DSN` set (add upload vars to confirm maps).
2. Open **`/learning/elk`** on the deployed site and click **Demo JS error (Sentry)**.
3. Confirm a new issue under **Issues** in Sentry with symbolicated frames after maps are processed.

## Troubleshooting

- **`VITE_SENTRY_DSN` unset**: SDK stays silent by design; check `.env` and rebuild frontend (`pnpm build`).
- **pnpm “Ignored build scripts: @sentry/cli”**: Source map uploads need the CLI binary. Run **`pnpm approve-builds`** (enable **`@sentry/cli`**), then **`pnpm install`** again if uploads fail.
- **Events blocked / CORS**: Align Sentry allowed origins with your app URL; ensure DSN scheme/host matches what the browser loads.
- **Maps not applying**: **`SENTRY_RELEASE`** must equal **`VITE_SENTRY_RELEASE`**; re-run **`pnpm build`** after changing release names.
