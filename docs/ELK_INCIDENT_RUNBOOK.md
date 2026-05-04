# ELK lab — dashboard, alert, and incident runbook

Companion to [ELK_LEARNING_LAB.md](../ELK_LEARNING_LAB.md). Goal: move from “logs exist” to **one actionable visualization**, **one alert**, and a short **operator playbook**.

Assumes data views exist for application logs (`codeigniter-app-logs-*`) and APM indices (`logs-apm-*`, `traces-apm-*`, `errors-apm-*`) as described in the ELK lab.

## Dashboard recipe — “Portal reliability”

Create a **Dashboard** with three panels:

### Panel A — HTTP 5xx volume (logs)

1. **Analytics → Discover**
2. Data view `codeigniter-app-logs-*`
3. KQL: `http.response.status_code >= 500`
4. Save → **Lens** visualization → vertical bar over `@timestamp`
5. Pin to dashboard **Portal reliability**

### Panel B — Slow requests (>750 ms)

KQL:

```text
labels.duration_ms > 750 AND event.dataset : "codeigniter.request"
```

Same workflow → stacked area or bar → add to dashboard.

### Panel C — APM transaction latency (p95)

1. **Observability → APM → Services → codeigniter-job-board**
2. Open **Latency** overview → note p95/p99 trend
3. **Save to dashboard** (or recreate equivalent from **`traces-apm-*`** in Lens using aggregation on `transaction.duration.us`)

Optional fourth tile: Discover `log.level : error AND NOT http.response.status_code : 404` to suppress noisy missing routes during scans.

Export/share: **Stack Management → Saved Objects → Export** include your dashboard when you want GitOps-style backups.

## Alert recipe — spike in 5xx responses

Use **Observability → Alerts & Rules** (or **Stack Management → Rules** depending on Kibana layout):

1. Rule type: **Custom threshold** / **Elasticsearch query** on **`codeigniter-app-logs-*`**
2. Query: `http.response.status_code >= 500`
3. Metric: **count** grouped over **5 minutes**
4. Threshold: **> 5 documents** in **15 minutes** (tune for lab traffic)
5. Connector: Email/Slack/Webhook as available (learning stacks often skip connectors — document manual polling instead)

Alternative APM-centric rule: threshold on **`transaction.failure_count`** or **`event.outcome : failure`** for service **`codeigniter-job-board`** over the same windows.

### Queue pressure hook

Queue failures often surface as **`log.level:error`** lines mentioning **`queue`** / **`Job`** handlers if your worker logs exceptions to stdout/Filebeat paths. Temporarily widen Discover:

```text
log.level : error AND message : queue
```

Promote recurring phrases into structured fields later (`labels.queue_job`, etc.) when you instrument workers heavily.

## Incident runbook — “5xx spike fired”

**Impact**: Users cannot browse/post reliably.

**Investigate (≤10 minutes)**

1. Open dashboard **Portal reliability** — confirm spike aligns with alert window (rule out ingestion lag).
2. Discover drill-down:
   - `http.response.status_code >= 500`
   - Sort descending `@timestamp`
   - Expand one row → note `labels.route`, `labels.controller`, PHP stack traces if present.
3. Open **APM → Errors** for **`codeigniter-job-board`** matching timestamps — correlate PHP exceptions vs infra timeouts.
4. Check infra basics on VPS/LXC lab hosts:
   - `systemctl status php*-fpm nginx mysql`
   - Disk usage (`df -h`), inode exhaustion (`df -i`).
   - Recent deploy — migrations stuck? **`php spark migrate --all`** logs?

**Mitigate**

1. Roll back **`current`** symlink release if regression confirmed ([`ops/lxc/DEPLOY_CHECKLIST.md`](../ops/lxc/DEPLOY_CHECKLIST.md)).
2. Toggle **`MAINTENANCE_MODE`** / feature flags only if your playbook defines safe semantics ([`app/Config/FeatureFlags.php`](../app/Config/FeatureFlags.php)).
3. Scale/read replicas only after confirming DB saturation via metrics — avoid guessing.

**Communicate**

- Record timeline: detection → mitigation → root cause hypothesis → permanent fix ticket.

**Post-incident**

- Add missing logs at fatal branches if troubleshooting blind spots persisted &gt;15 minutes.
