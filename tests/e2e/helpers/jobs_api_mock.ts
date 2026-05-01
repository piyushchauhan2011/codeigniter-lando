import { readFileSync } from "node:fs";
import { join } from "node:path";

import type { Page } from "@playwright/test";

export function readJobsApiFixture(): { jobs: unknown[] } {
  const json = readFileSync(join(process.cwd(), "tests/e2e/fixtures/jobs_api.json"), "utf8");
  return JSON.parse(json) as { jobs: unknown[] };
}

export async function mockJobsApiGet(page: Page, body: { jobs: unknown[] }): Promise<void> {
  await page.route("**/api/jobs", async (route) => {
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
