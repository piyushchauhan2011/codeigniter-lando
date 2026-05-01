import { expect, test } from "@playwright/test";

import { ensureBackboneFilterHarness } from "./helpers/backbone_jobs_harness";
import { mockJobsApiGet, readJobsApiFixture } from "./helpers/jobs_api_mock";

test.describe("Backbone jobs index view (UI)", () => {
  test.beforeEach(async ({ page }) => {
    await mockJobsApiGet(page, readJobsApiFixture());
  });

  test("client filter select shows only matching employment types after change", async ({ page }) => {
    const fixture = readJobsApiFixture();
    await page.goto("/jobs");

    await expect(
      page.getByText(`Live catalog: ${fixture.jobs.length} published opening(s) reported by the API.`),
    ).toBeVisible();

    await ensureBackboneFilterHarness(page);

    const filter = page.locator("[data-client-filter-type]");
    const alpha = page.locator("[data-e2e-backbone-card=alpha]");
    const beta = page.locator("[data-e2e-backbone-card=beta]");

    await expect(alpha).toBeVisible();
    await expect(beta).toBeVisible();

    await filter.selectOption("contract");

    await expect(alpha).toBeHidden();
    await expect(beta).toBeVisible();

    await filter.selectOption("full_time");

    await expect(alpha).toBeVisible();
    await expect(beta).toBeHidden();

    await filter.selectOption("");

    await expect(alpha).toBeVisible();
    await expect(beta).toBeVisible();
  });

  test("banner reflects mocked API collection size (Backbone fetch + parse path)", async ({ page }) => {
    const threeJobs = {
      jobs: [
        { id: 1, employment_type: "full_time", title: "One" },
        { id: 2, employment_type: "part_time", title: "Two" },
        { id: 3, employment_type: "contract", title: "Three" },
      ],
    };

    await page.unroute("**/api/jobs");
    await mockJobsApiGet(page, threeJobs);
    await page.goto("/jobs");

    await expect(
      page.getByText("Live catalog: 3 published opening(s) reported by the API."),
    ).toBeVisible();
  });
});
