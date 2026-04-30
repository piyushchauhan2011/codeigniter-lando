import { defineConfig, type PlaywrightTestConfig } from "@playwright/test";

const port = process.env.PLAYWRIGHT_PORT ?? "8080";
const localOrigin = `http://127.0.0.1:${port}`;
const explicitBaseURL = process.env.PLAYWRIGHT_BASE_URL?.trim();
const baseURL = explicitBaseURL || localOrigin;

const ignoreHTTPSErrors =
  process.env.PLAYWRIGHT_IGNORE_HTTPS_ERRORS === "1" ||
  process.env.PLAYWRIGHT_IGNORE_HTTPS_ERRORS === "true";

/** When PLAYWRIGHT_BASE_URL is set, Playwright assumes the app is already up (e.g. `lando start`). */
const managedLocalServer = !explicitBaseURL;

const config: PlaywrightTestConfig = {
  testDir: "tests/e2e",
  timeout: 30_000,
  use: {
    baseURL,
    ignoreHTTPSErrors,
  },
};

if (managedLocalServer) {
  config.webServer = {
    command: `php spark serve --host 127.0.0.1 --port ${port}`,
    url: localOrigin,
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
  };
}

export default defineConfig(config);
