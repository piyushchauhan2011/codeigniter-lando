# Security hardening notes (learning checklist)

Concrete OWASP-aligned practices wired into this repo — useful for interviews and VPS deployments.

## 1. HTTPS, cookies, and CSRF

- **Production cookies**: [`app/Config/Cookie.php`](../app/Config/Cookie.php) sets `secure = true` when `ENVIRONMENT === 'production'`. Session cookies inherit these defaults via CodeIgniter’s cookie stack.
- **CSRF**: Portal HTML forms stay behind session-backed CSRF ([`app/Config/Security.php`](../app/Config/Security.php)). JSON **`/api/*`** routes are excluded from CSRF so mobile/clients can consume read-only endpoints without browser form tokens ([`app/Config/Filters.php`](../app/Config/Filters.php)).
- **Reverse proxy**: Terminate TLS at nginx/Caddy with trusted certs (`mkcert` locally; real CA in production). Align `app.baseURL` with the HTTPS origin — mismatches cause subtle redirect/asset bugs ([LANDO_ONBOARDING.md](../LANDO_ONBOARDING.md), [docs/LXC_VPS_LAB.md](LXC_VPS_LAB.md)).

## 2. Authorization beyond authentication

Being logged in is not the same as being authorized for every object.

- Employer mutations always compare **`employer_user_id`** on `portal_jobs` (and asset ownership) against the authenticated user — never trust IDs from the client alone ([`Employer::editJob`](../app/Controllers/Employer.php), [`EmployerAssets`](../app/Controllers/EmployerAssets.php)).
- Automated regression: [`tests/feature/PortalAuthorizationFeatureTest.php`](../tests/feature/PortalAuthorizationFeatureTest.php) proves cross-employer job IDs raise **`PageNotFoundException` (HTTP 404)** without exposing another employer’s row.

## 3. Upload surface (MIME + extension)

- Job asset uploads validate **extension allow-list** **and** **`mime_in`** against server-detected types ([`app/Config/Validation.php`](../app/Config/Validation.php) `$portal_job_asset_upload`).
- Employer logo uploads mirror MIME checks inline in [`Employer::updateProfile`](../app/Controllers/Employer.php).
- Objects land in private object storage keys; downloads use short-lived signed URLs — reduces direct path traversal exposure.

### Threat-model snippet (say this out loud)

“Untrusted uploads never execute as PHP under the web root; we gatekeeper MIME/extension, store outside docroot/S3-compatible storage, and authorize downloads per employer.”

## Related knobs

| Concern | Where |
|--------|--------|
| Login brute-force pacing | [`LoginThrottleFilter`](../app/Filters/LoginThrottleFilter.php), [`app/Config/LoginThrottle.php`](../app/Config/LoginThrottle.php) |
| API pacing | [`ApiThrottleFilter`](../app/Filters/ApiThrottleFilter.php), nginx `limit_req` ([`ops/lxc/nginx-reverse-proxy-example.conf`](../ops/lxc/nginx-reverse-proxy-example.conf)) |
| OpenAPI truth | [`openapi/openapi-v1.yaml`](../openapi/openapi-v1.yaml) |
