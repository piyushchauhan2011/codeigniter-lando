import fs from "node:fs/promises";
import path from "node:path";

const root = process.cwd();
const distJs = path.join(root, "public/assets/dist/js");
/** Prefer HTTPS so Kibana matches SERVER_PUBLICBASEURL (see .lando.yml). Plain HTTP :8080 breaks COOP/console behavior and can confuse uploads vs browser URLs. */
const kibanaUrl = trimSlash(process.env.ELASTIC_KIBANA_URL ?? "https://kibana-my-first-lamp-app.lndo.site");
const publicBaseUrl = trimSlash(process.env.ELASTIC_PUBLIC_BASE_URL ?? "https://my-first-lamp-app.lndo.site");

relaxTlsForLocalLandoHttps(kibanaUrl);
const serviceName = process.env.ELASTIC_RUM_SERVICE_NAME ?? "codeigniter-job-board-rum";
const serviceVersion = process.env.ELASTIC_SERVICE_VERSION ?? "local-dev";

const entries = await fs.readdir(distJs, { withFileTypes: true });
const maps = entries
  .filter((entry) => entry.isFile() && entry.name.endsWith(".js.map"))
  .map((entry) => entry.name);

if (maps.length === 0) {
  throw new Error(`No JS source maps found in ${distJs}. Run pnpm build first.`);
}

for (const mapName of maps) {
  const jsName = mapName.replace(/\.map$/, "");
  const sourceMapPath = path.join(distJs, mapName);
  const sourceMap = await fs.readFile(sourceMapPath, "utf8");

  /**
   * APM matches uploaded maps by bundle_filepath against stack frame URLs.
   * Frames often use a path-only filename (e.g. `/assets/dist/js/portal.js`) even when the script tag uses an absolute URL — upload both.
   */
  const bundlePaths = uniqueBundleFilepaths(jsName, publicBaseUrl);
  const extras = parseCommaSeparatedUrls(process.env.ELASTIC_SOURCEMAP_EXTRA_BUNDLE_URLS);
  for (const extra of extras) {
    bundlePaths.add(trimSlash(extra));
  }

  for (const bundle_filepath of bundlePaths) {
    await uploadOneSourcemap({
      bundle_filepath,
      sourceMap,
      mapName,
    });
  }
}

function uniqueBundleFilepaths(jsName, baseUrl) {
  const paths = new Set([
    `${baseUrl}/assets/dist/js/${jsName}`,
    `/assets/dist/js/${jsName}`,
  ]);
  return paths;
}

function parseCommaSeparatedUrls(value) {
  if (!value || typeof value !== "string") {
    return [];
  }

  return value
    .split(",")
    .map((s) => s.trim())
    .filter(Boolean);
}

async function uploadOneSourcemap({ bundle_filepath, sourceMap, mapName }) {
  const form = new FormData();

  form.set("service_name", serviceName);
  form.set("service_version", serviceVersion);
  form.set("bundle_filepath", bundle_filepath);
  form.set("sourcemap", sourceMap);

  const endpoint = `${kibanaUrl}/api/apm/sourcemaps`;
  const response = await fetch(endpoint, {
    method: "POST",
    headers: {
      "elastic-api-version": "2023-10-31",
      "kbn-xsrf": "codeigniter-elk-lab",
    },
    body: form,
  });

  if (!response.ok) {
    const body = await response.text();
    throw new Error(
      `Source map upload failed for ${mapName} (${bundle_filepath}): POST ${endpoint} → HTTP ${response.status} ${body}`,
    );
  }

  console.log(`Uploaded ${mapName} → bundle_filepath=${bundle_filepath} (${serviceName}@${serviceVersion})`);
}

function trimSlash(value) {
  return value.replace(/\/$/, "");
}

/**
 * Lando terminates TLS with a local CA; Node fetch otherwise fails CERTIFICATE_VERIFY_FAILED.
 * Scoped to this script process only. Set ELASTIC_KIBANA_TLS_VERIFY=1 to enforce verification (e.g. CI against real certs).
 */
function relaxTlsForLocalLandoHttps(urlString) {
  if (process.env.ELASTIC_KIBANA_TLS_VERIFY === "1") {
    return;
  }

  let url;
  try {
    url = new URL(urlString);
  } catch {
    return;
  }

  if (url.protocol !== "https:" || !url.hostname.endsWith(".lndo.site")) {
    return;
  }

  process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";
}
