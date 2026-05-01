import { expect, test } from "@playwright/test";

import { mockJobsApiGet, readJobsApiFixture } from "./helpers/jobs_api_mock";

const fakeJobs = readJobsApiFixture();

test.describe("job listings API banner (mocked GET /api/jobs)", () => {
  test.beforeEach(async ({ page }) => {
    await mockJobsApiGet(page, fakeJobs);
  });

  test("jobs page shows count text from stubbed API response", async ({ page }) => {
    await page.goto("/jobs");

    await expect(
      page.getByText(`Live catalog: ${fakeJobs.jobs.length} published opening(s) reported by the API.`),
    ).toBeVisible();
  });
});
