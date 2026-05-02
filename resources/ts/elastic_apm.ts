import { init as initApm, type ApmBase } from "@elastic/apm-rum";

let initialized = false;

let apmAgent: ApmBase | undefined;

/** Initialized agent after {@link initElasticApm}; use for {@link ApmBase.captureError}. */
export function getElasticApm(): ApmBase | undefined {
  return apmAgent;
}

/**
 * Same-origin APM intake proxy (`App\Controllers\ApmProxy`).
 * Avoids mixed content (HTTPS page → HTTP apm) and TLS CN mismatch on `apm.*`
 * under Lando (cert matches the main app host, not every proxy hostname).
 * Stack frames still reference the real script URL (`…/assets/dist/js/portal.js`); source maps depend on `pnpm elastic:sourcemaps` + matching `bundle_filepath`, not on bypassing this proxy.
 */
function defaultApmServerUrl(): string {
  if (typeof window !== "undefined") {
    return `${window.location.origin}/__apm-proxy`;
  }

  return "http://apm-my-first-lamp-app.lndo.site:8000";
}

export function initElasticApm(): void {
  if (initialized || import.meta.env.VITE_ELASTIC_APM_ENABLED === "0") {
    return;
  }

  initialized = true;

  apmAgent = initApm({
    serviceName: import.meta.env.VITE_ELASTIC_APM_SERVICE_NAME ?? "codeigniter-job-board-rum",
    serviceVersion: import.meta.env.VITE_ELASTIC_APM_SERVICE_VERSION ?? "local-dev",
    environment: import.meta.env.MODE,
    serverUrl: import.meta.env.VITE_ELASTIC_APM_SERVER_URL ?? defaultApmServerUrl(),
    distributedTracingOrigins: [window.location.origin],
  });

  apmAgent.setCustomContext({
    app: "CodeIgniter job board",
    sourceMaps: "Upload with pnpm elastic:sourcemaps after pnpm build",
  });
}
