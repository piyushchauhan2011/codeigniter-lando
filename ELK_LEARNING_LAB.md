# ELK Learning Lab

This lab adds a local Elastic stack to the CodeIgniter job board so you can learn search, logs, Kibana analysis, APM error tracking, and source-mapped browser errors.

## Services

After `lando rebuild -y && lando start`, these local services are available:

- App: `https://my-first-lamp-app.lndo.site`
- Kibana (HTTPS, TLS-friendly hostname): `https://kibana-my-first-lamp-app.lndo.site`
- **Browser RUM intake**: same origin as the app — `https://my-first-lamp-app.lndo.site/__apm-proxy` (proxied by `App\Controllers\ApmProxy` to `apm-server`). This avoids mixed content (HTTPS → HTTP apm) and `ERR_CERT_COMMON_NAME_INVALID` when `https://apm.*` does not match your Lando TLS certificate.
- Direct APM URL (CLI / debugging inside Docker): `http://apm-server:8200`
- HTTP port bindings (optional, for CLI / plain HTTP browsing): Kibana `http://kibana-my-first-lamp-app.lndo.site:8000`, APM `http://apm-my-first-lamp-app.lndo.site:8000`

Lando’s proxy TLS certificate covers **`*.lndo.site`** (exactly **one** DNS label before `.lndo.site`). Hostnames like **`kibana.my-first-lamp-app.lndo.site`** sit **two** levels under `.lndo.site`, so they are **outside** that wildcard — browsers show invalid HTTPS / wrong routes. This lab therefore uses **`kibana-my-first-lamp-app.lndo.site`** (hyphenated single label). After changing `.lando.yml`, run **`lando rebuild -y`** (or at least **`lando restart`**).
- Elasticsearch inside Lando: `http://elasticsearch:9200` (PHP uses the official **[elasticsearch-php](https://github.com/elastic/elasticsearch-php)** client via `Config\Services::elasticsearch()` and `App\Libraries\Elastic\ElasticClient`.)
- **PHP APM agent**: the Lando appserver installs Elastic’s **`.deb`** on `lando rebuild` (see `appserver.build_as_root` in `.lando.yml`). Verify with `lando php -m | grep -i elastic`. Traces use **`codeigniter-job-board`** in Kibana (browser RUM stays **`codeigniter-job-board-rum`**). Running Xdebug and the APM extension together is possible in this lab but not ideal for production.

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
2. On `/learning/elk`, use **Write logs**, **Handled exception**, **Slow request**, **Throw JS error** as needed.
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

### 4. APM traces and errors (browser RUM)

APM data lands in Elasticsearch indices such as `traces-apm-*`, `logs-apm-*`, `metrics-apm-*`. You do **not** need Enterprise Search to view them.

1. Main menu → **Observability** → **APM** (wording may be **Applications** / **Services**).
2. Open **Services**. You should see the RUM service (default name **`codeigniter-job-board-rum`** once the browser bundle has sent events through `/__apm-proxy`).
3. Click the service → **Transactions**, **Errors**, **Spans** to inspect traffic and JS errors (including stack traces after source map upload).

If **Services** is empty: confirm the portal was rebuilt (`pnpm build`), hit `/learning/elk` over HTTPS, check the browser Network tab for successful `POST` to `…/__apm-proxy/intake/v2/rum/events`, and widen the time range.

### 5. Optional: confirm indexes without Enterprise Search

**Stack Management** → **Index Management** lists Elasticsearch indexes (for example `codeigniter-app-logs-*`, `traces-apm-*`). Use this to verify ingestion without opening Enterprise Search.

## Source-Mapped JS Errors

Vite already emits JS source maps for production-style builds. Upload them to Kibana after building:

```bash
pnpm build
pnpm elastic:sourcemaps
```

Useful environment overrides:

```bash
# Default ELASTIC_KIBANA_URL is HTTPS (matches Kibana SERVER_PUBLICBASEURL). On *.lndo.site the upload script skips TLS verification for this Node process only unless ELASTIC_KIBANA_TLS_VERIFY=1.
ELASTIC_PUBLIC_BASE_URL=https://my-first-lamp-app.lndo.site \
ELASTIC_RUM_SERVICE_NAME=codeigniter-job-board-rum \
ELASTIC_SERVICE_VERSION=local-dev \
pnpm elastic:sourcemaps
```

Same-origin **`/__apm-proxy`** only fixes TLS/mixed-content for sending events; it does **not** change stack frame URLs (those still point at **`…/assets/dist/js/portal.js`**). If stacks still won’t symbolicate, align **`ELASTIC_PUBLIC_BASE_URL`** with the URL you actually use in the browser and re-upload maps.

The upload script registers each map twice: **`ELASTIC_PUBLIC_BASE_URL`/assets/dist/js/…** (matches your script tag URL) and **`/assets/dist/js/…`** (matches path-only stack frames). If you load the app under another absolute URL (for example plain HTTP on port `8000`), add full bundle URLs:

```bash
ELASTIC_SOURCEMAP_EXTRA_BUNDLE_URLS=http://my-first-lamp-app.lndo.site:8000/assets/dist/js/portal.js pnpm elastic:sourcemaps
```

Open `/learning/elk` and use **Demo JS error (APM)**. The RUM agent calls `captureError` so Elasticsearch receives stack frames; uncaught-only flows often show an empty trace in Kibana even when the culprit line hints at TypeScript paths.

Requirements that must line up with the upload:

- **service name** (`codeigniter-job-board-rum` unless you override `VITE_ELASTIC_APM_SERVICE_NAME` / `ELASTIC_RUM_SERVICE_NAME`)
- **service version** (`local-dev` unless you override `VITE_ELASTIC_APM_SERVICE_VERSION` / `ELASTIC_SERVICE_VERSION`)
- **`bundle_filepath`** matching how frames reference `portal.js` (handled by the dual upload above)

After changing TypeScript or bumping versions, run **`pnpm build`** again and **`pnpm elastic:sourcemaps`** before expecting updated stacks in APM.

If symbolication worked, Elasticsearch stores frames with **`error.exception.stacktrace.sourcemap.updated: true`**, **`filename`** pointing at **`resources/ts/...`**, and a **`line.context`** snippet. When lookup fails you may see **`sourcemap.error`** mentioning **`503`** or similar — regenerate the error after Elasticsearch is idle, or restart **`apm-server`** / **`elasticsearch`**. The APM UI picks **one sample** per view; open another occurrence if “sample 2 of 2” failed mapping while sample 1 succeeded.

Production bundles use **`sourcemap: "hidden"`** in `vite.config.ts`: `.map` files still exist for upload, but the browser does not see `//# sourceMappingURL` in `portal.js`, so `Error.stack` keeps **`…/assets/dist/js/portal.js`** locations that match `bundle_filepath`. Without this, Chromium may rewrite stacks to paths like `../../../../resources/ts/…`, which APM Server cannot tie to your uploaded map and may drop frames (Kibana shows **“No stack trace available”**).

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
5. Upload source maps and verify JS stack traces resolve to TypeScript source paths.

## Advanced Exercises

1. Add an index alias such as `codeigniter-jobs-current` and practice zero-downtime reindexing.
2. Add a synonym analyzer for terms like `remote`, `work from home`, and `distributed`.
3. Add an ingest pipeline that normalizes request paths or extracts route segments.
4. Use correlation IDs to pivot from a request log to related PHP errors and browser events.
5. Create Kibana alerting rules conceptually for high error rate, slow requests, and searches with zero results.
6. Explore ILM concepts for rotating `codeigniter-app-logs-*` indexes.

## Troubleshooting

- **Mixed content / RUM “HTTP status: 0”**: Use the bundled default `serverUrl` (`{origin}/__apm-proxy`) so intake stays same-origin with your app TLS certificate. After pulling changes, run `pnpm build`. Override only if needed: `VITE_ELASTIC_APM_SERVER_URL`.
- **`ERR_CERT_COMMON_NAME_INVALID` on `https://apm.…`**: Lando often issues a cert for the main app host, not every `*.my-first-lamp-app.lndo.site` service. Do not point RUM at `https://apm…` unless you fix TLS for that hostname; the `/__apm-proxy` route is the supported workaround.
- **Kibana console: Cross-Origin-Opener-Policy / “untrustworthy origin”**: You opened Kibana over plain **HTTP** (e.g. `http://…:8080`). Browsers ignore COOP on non-HTTPS origins; use **`https://kibana-my-first-lamp-app.lndo.site`** so behavior matches `SERVER_PUBLICBASEURL`. This does **not** fix missing APM stacks by itself.
- **HTTPS to Kibana shows “not secure” or 404**: You are probably using a **nested** hostname such as **`kibana.my-first-lamp-app.lndo.site`**, which is **not** covered by Lando’s `*.lndo.site` wildcard. Use **`https://kibana-my-first-lamp-app.lndo.site`** and run **`lando rebuild -y`** after pulling proxy changes.
- **Kibana console: `Encrypted Saved Objects` / AI Assistant 500**: Set `XPACK_ENCRYPTEDSAVEDOBJECTS_ENCRYPTIONKEY` on the Kibana container (see `.lando.yml`), then `lando restart kibana`. That clears plugin noise unrelated to RUM stacks.
- If Kibana is not ready, wait a minute and rerun `lando elastic-health`.
- A yellow Elasticsearch health state can be normal for this single-node lab when indexes have replica shards; red is the state to investigate.
- If source map upload fails: confirm **`pnpm build`** produced `public/assets/dist/js/*.map`, **`lando start`** is up, and you use **`https://kibana…`** (default for `pnpm elastic:sourcemaps`). For `*.lndo.site` HTTPS the upload script relaxes TLS for that Node process unless **`ELASTIC_KIBANA_TLS_VERIFY=1`**.
- **APM shows culprit path but “No stack trace available”**: Often the stored document has **`error.exception.stacktrace.sourcemap.error`** (e.g. Elasticsearch **503** while APM Server loads map metadata) — retry after ES is idle, restart **`apm-server`** / **`elasticsearch`**, or trigger a **new** demo error. Also run `pnpm build` (hidden JS sourcemaps), then `pnpm elastic:sourcemaps`; align `ELASTIC_PUBLIC_BASE_URL` with `app.baseURL`; restart **`apm-server`** after `.lando.yml` changes (`lando restart apm-server`).
- If Elasticsearch search falls back to SQL, run `lando elastic-reindex-jobs` and check the app log for `codeigniter.search` errors.
- If **PHP APM** never appears in Kibana, run **`lando rebuild -y`** so the Elastic `.deb` installs, then **`lando php -m | grep -i elastic`**. Tune `.lando/php.ini` (`elastic_apm.*`) if needed.
- **Enterprise Search errors / empty “Elasticsearch indices”**: Ignore that area unless you deploy Enterprise Search. Use **Discover** (`codeigniter-app-logs-*`) and **Observability → APM** per [Seeing logs and APM in Kibana](#seeing-logs-and-apm-in-kibana).
