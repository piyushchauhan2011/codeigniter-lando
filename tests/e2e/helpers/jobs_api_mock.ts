import { readFileSync } from "node:fs";
import { join } from "node:path";

import { expect, type Page } from "@playwright/test";

/** Matches `/api/jobs` and `/api/jobs?...` (jQuery/Backbone GET may add a query string). */
export const JOBS_API_GET_URL_RE = /\/api\/jobs(\?.*)?$/;

export function readJobsApiFixture(): { jobs: unknown[] } {
  const json = readFileSync(join(process.cwd(), "tests/e2e/fixtures/jobs_api.json"), "utf8");
  return JSON.parse(json) as { jobs: unknown[] };
}

export async function mockJobsApiGet(page: Page, body: { jobs: unknown[] }): Promise<void> {
  await page.route(JOBS_API_GET_URL_RE, async (route) => {
    if (route.request().method() !== "GET") {
      await route.continue();
      return;
    }
    await route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify(body),
    });
  });
}

/** Banner copy is translated (en/fr); always assert via `[data-job-api-banner-text]` and the numeric count. */
export async function expectJobsApiBannerCount(page: Page, count: number): Promise<void> {
  const text = page.locator("[data-job-api-banner-text]");
  await expect(text).toBeVisible({ timeout: 15_000 });
  await expect(text).toHaveText(new RegExp(`\\b${count}\\b`));
}
