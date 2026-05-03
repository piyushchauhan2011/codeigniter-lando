# ELK Learning Lab

This lab adds a local Elastic stack to the CodeIgniter job board so you can learn search, logs, Kibana analysis, and **PHP** APM error tracking in Elasticsearch. **Browser / JavaScript errors** use **[GlitchTip](https://glitchtip.com)** (Sentry-compatible) running in Lando — see [docs/GLITCHTIP_LANDO.md](docs/GLITCHTIP_LANDO.md).

## Services

After `lando rebuild -y && lando start`, these local services are available:

- App: `https://my-first-lamp-app.lndo.site`
- Kibana (HTTPS, TLS-friendly hostname): `https://kibana-my-first-lamp-app.lndo.site`
- **GlitchTip** (browser JS errors): **`https://glitchtip-my-first-lamp-app.lndo.site/login`** (HTTP **`http://…:8080/login`** fallback) — run **`lando glitchtip-url`** for reminders.
- Direct APM URL (CLI / debugging inside Docker): `http://apm-server:8200`
- HTTP port bindings (optional, for CLI / plain HTTP browsing): Kibana `http://kibana-my-first-lamp-app.lndo.site:8000`, APM `http://apm-my-first-lamp-app.lndo.site:8000`

Lando’s proxy TLS certificate covers **`*.lndo.site`** (exactly **one** DNS label before `.lndo.site`). Hostnames like **`kibana.my-first-lamp-app.lndo.site`** sit **two** levels under `.lndo.site`, so they are **outside** that wildcard — browsers show invalid HTTPS / wrong routes. This lab therefore uses **`kibana-my-first-lamp-app.lndo.site`** (hyphenated single label). After changing `.lando.yml`, run **`lando rebuild -y`** (or at least **`lando restart`**).
- Elasticsearch inside Lando: `http://elasticsearch:9200` (PHP uses the official **[elasticsearch-php](https://github.com/elastic/elasticsearch-php)** client via `Config\Services::elasticsearch()` and `App\Libraries\Elastic\ElasticClient`.)
- **PHP APM agent**: the Lando appserver installs Elastic’s **`.deb`** on `lando rebuild` (see `appserver.build_as_root` in `.lando.yml`). Verify with `lando php -m | grep -i elastic`. The PHP service name in Kibana APM is **`codeigniter-job-board`**. Running Xdebug and the APM extension together is possible in this lab but not ideal for production.

This is a local learning setup. Elasticsearch security is disabled in `.lando.yml` so you can focus on concepts before production hardening.
The local Elasticsearch container also disables disk allocation watermarks so the lab can run on a nearly full development machine; do not use that setting in production.

## First Run

```bash
lando rebuild -y
lando start
lando php spark migrate --all
lando php spark db:seed JobPortalDemoSeeder
lando elastic-health
lando elastic-reindex-jobs
```

Open Kibana and create these data views:

- `codeigniter-app-logs-*` with `@timestamp`
- `traces-apm*` with `@timestamp`
- `logs-apm*` with `@timestamp`
- `errors-apm*` with `@timestamp`
- `codeigniter-jobs-v1` for job-search documents

## Seeing logs and APM in Kibana

Enterprise Search areas such as **Elasticsearch → Content → Elasticsearch indices** are optional commercial/search-app features. This lab does **not** run Enterprise Search; errors there do **not** mean Elasticsearch or APM are broken.

### 1. Generate data once

1. Browse your app (HTTPS): `/learning/elk`, `/jobs`, etc.
2. On `/learning/elk`, use **Write logs**, **Handled exception**, **Slow request**, **Demo JS error (GlitchTip)**, etc., as needed.
3. Ensure Filebeat is running (`lando restart filebeat` if logs never appear).

### 2. Create data views (first-time setup)

1. Open Kibana over **`https://kibana-my-first-lamp-app.lndo.site`** (matches `SERVER_PUBLICBASEURL` and Lando’s `*.lndo.site` cert). Plain HTTP `:8000` / `:8080` is fine for quick CLI checks.
2. Open the **main menu** (top left) → **Management** → **Stack Management**.
3. Under **Kibana**, open **Data Views** → **Create data view**.
4. Add each pattern from the list above with **Timestamp field** `@timestamp` (for index patterns ending in `*`).
5. For the rollup-style jobs index `codeigniter-jobs-v1`, create a data view with that exact name if it exists after `lando elastic-reindex-jobs`; pick `@timestamp` if present, otherwise Kibana may suggest another time field.

### 3. Application logs (Discover)

1. Main menu → **Analytics** → **Discover** (or **Discover** from the sidebar, depending on Kibana layout).
2. Choose data view **`codeigniter-app-logs-*`**.
3. Adjust the time picker (top right) to **Last 15 minutes** or **Last 24 hours**.
4. Filter with KQL, for example `event.dataset : "codeigniter.request"` or `log.level : "error"`.

### 4. APM traces and errors (PHP backend)

APM data lands in Elasticsearch indices such as `traces-apm-*`, `logs-apm-*`, `metrics-apm-*`. You do **not** need Enterprise Search to view them.

1. Main menu → **Observability** → **APM** (wording may be **Applications** / **Services**).
2. Open **Services**. You should see **`codeigniter-job-board`** once PHP requests have been traced (browse `/jobs`, `/learning/elk`, etc.).
3. Click the service → **Transactions**, **Errors**, **Spans** to inspect PHP traffic and exceptions.

Browser JavaScript errors are **not** sent to Elastic APM in this lab — configure **`VITE_GLITCHTIP_DSN`** and use GlitchTip **Issues** ([docs/GLITCHTIP_LANDO.md](docs/GLITCHTIP_LANDO.md)).

If **Services** is empty for PHP: confirm **`lando rebuild -y`** installed the agent, check **`lando php -m | grep -i elastic`**, browse the app over HTTPS, and widen the time range in Kibana.

### 5. Optional: confirm indexes without Enterprise Search

**Stack Management** → **Index Management** lists Elasticsearch indexes (for example `codeigniter-app-logs-*`, `traces-apm-*`). Use this to verify ingestion without opening Enterprise Search.

## Browser JS errors (GlitchTip)

The portal loads **`@sentry/browser`** with a **tunnel** to **`POST /learning/elk/glitchtip-tunnel`** so HTTPS pages forward envelopes to GlitchTip via PHP (see [docs/GLITCHTIP_LANDO.md](docs/GLITCHTIP_LANDO.md)). After **`pnpm build`** with **`VITE_GLITCHTIP_DSN`** set, open **`/learning/elk`** and use **Demo JS error (GlitchTip)** — events appear under GlitchTip **Issues**.

Optional **hidden source maps**: when **`SENTRY_AUTH_TOKEN`**, **`SENTRY_ORG`**, **`SENTRY_PROJECT`**, **`SENTRY_RELEASE`**, and **`SENTRY_URL`** (GlitchTip base URL, e.g. **`https://glitchtip-my-first-lamp-app.lndo.site`** or **`http://…:8080`** for HTTP-only labs) are set, **`pnpm build`** runs **`@sentry/vite-plugin`** and uploads maps for that release. **`release.inject`** aligns the bundle with GlitchTip; you can also set **`VITE_GLITCHTIP_RELEASE`** to match **`SENTRY_RELEASE`**.

Full setup (first-run signup, tokens, **`pnpm approve-builds`** for **`@sentry/cli`**): [docs/GLITCHTIP_LANDO.md](docs/GLITCHTIP_LANDO.md).

Production-style bundles use **`sourcemap: "hidden"`** in `vite.config.ts`: `.map` files exist for upload, but the browser does not ship `//# sourceMappingURL` in `portal.js`, so stacks reference **`…/assets/dist/js/portal.js`** URLs that match uploaded artifacts.

## PHP Errors And Logs

Open `/learning/elk` to generate:

- Structured info/warning logs
- Handled PHP exception logs
- Unhandled PHP exceptions for APM grouping
- Slow requests
- 404 events

CodeIgniter writes normal text logs and JSON logs under `writable/logs`. Filebeat ships `log-*.json` into `codeigniter-app-logs-*`.

Useful Kibana Discover queries:

```text
event.dataset: codeigniter.request
event.dataset: codeigniter.elk_lab
log.level: error
http.response.status_code >= 500
labels.duration_ms > 500
```

PHP file and line numbers are present in PHP stack traces. For full PHP transaction/error capture in APM, install and enable the Elastic APM PHP extension in the appserver; `.lando/php.ini` already contains the local APM server settings.

## Job Search

Index jobs:

```bash
lando elastic-reindex-jobs
lando elastic-search-jobs --q=php
lando elastic-search-jobs --q=api --location=remote
```

In the browser, open `/jobs` and switch from SQL to Elasticsearch using the link above the filter form. Elasticsearch mode demonstrates:

- `multi_match` over title, description, company, and location
- Fuzzy matching
- Keyword filters for employment type and category
- Sorting by featured and created date
- Aggregations for employment type, category, and location

## Basic Exercises

1. Run `lando elastic-health` and inspect the Elasticsearch cluster health.
2. Run `lando elastic-reindex-jobs`, then search for `php` and `writer`.
3. Open Kibana Discover with `codeigniter-app-logs-*`.
4. Visit `/learning/elk/log-demo` and find the generated events.
5. Compare `/jobs` SQL mode with Elasticsearch mode.

## Intermediate Exercises

1. Inspect the `codeigniter-jobs-v1` mapping and identify `text`, `keyword`, `integer`, `boolean`, and `date` fields.
2. Run job searches with filters and compare hits with aggregation buckets.
3. Generate slow requests and build a Kibana lens chart for `labels.duration_ms`.
4. Trigger handled and unhandled PHP errors, then compare logs with APM error groups.
5. Configure GlitchTip and verify JS stacks resolve to TypeScript after optional source map upload ([docs/GLITCHTIP_LANDO.md](docs/GLITCHTIP_LANDO.md)).

## Advanced Exercises

1. Add an index alias such as `codeigniter-jobs-current` and practice zero-downtime reindexing.
2. Add a synonym analyzer for terms like `remote`, `work from home`, and `distributed`.
3. Add an ingest pipeline that normalizes request paths or extracts route segments.
4. Use correlation IDs to pivot from a request log to related PHP errors (browser errors live in GlitchTip).
5. Create Kibana alerting rules conceptually for high error rate, slow requests, and searches with zero results.
6. Explore ILM concepts for rotating `codeigniter-app-logs-*` indexes.

## Troubleshooting

- **GlitchTip**: See [docs/GLITCHTIP_LANDO.md](docs/GLITCHTIP_LANDO.md) (`GLITCHTIP_DOMAIN`, DSN, auth token, **`pnpm approve-builds`** for **`@sentry/cli`**).
- **Kibana console: Cross-Origin-Opener-Policy / “untrustworthy origin”**: You opened Kibana over plain **HTTP** (e.g. `http://…:8080`). Browsers ignore COOP on non-HTTPS origins; use **`https://kibana-my-first-lamp-app.lndo.site`** so behavior matches `SERVER_PUBLICBASEURL`.
- **HTTPS to Kibana shows “not secure” or 404**: You are probably using a **nested** hostname such as **`kibana.my-first-lamp-app.lndo.site`**, which is **not** covered by Lando’s `*.lndo.site` wildcard. Use **`https://kibana-my-first-lamp-app.lndo.site`** and run **`lando rebuild -y`** after pulling proxy changes.
- **Kibana console: `Encrypted Saved Objects` / AI Assistant 500**: Set `XPACK_ENCRYPTEDSAVEDOBJECTS_ENCRYPTIONKEY` on the Kibana container (see `.lando.yml`), then `lando restart kibana`. That clears plugin noise unrelated to APM stacks.
- If Kibana is not ready, wait a minute and rerun `lando elastic-health`.
- A yellow Elasticsearch health state can be normal for this single-node lab when indexes have replica shards; red is the state to investigate.
- If Elasticsearch search falls back to SQL, run `lando elastic-reindex-jobs` and check the app log for `codeigniter.search` errors.
- If **PHP APM** never appears in Kibana, run **`lando rebuild -y`** so the Elastic `.deb` installs, then **`lando php -m | grep -i elastic`**. Tune `.lando/php.ini` (`elastic_apm.*`) if needed.
- **Enterprise Search errors / empty “Elasticsearch indices”**: Ignore that area unless you deploy Enterprise Search. Use **Discover** (`codeigniter-app-logs-*`) and **Observability → APM** per [Seeing logs and APM in Kibana](#seeing-logs-and-apm-in-kibana).
