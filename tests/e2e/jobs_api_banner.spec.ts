import { test } from "@playwright/test";

import { expectJobsApiBannerCount, mockJobsApiGet, readJobsApiFixture } from "./helpers/jobs_api_mock";

const fakeJobs = readJobsApiFixture();

test.describe("job listings API banner (mocked GET /api/jobs)", () => {
  test.beforeEach(async ({ page }) => {
    await mockJobsApiGet(page, fakeJobs);
  });

  test("jobs page shows count text from stubbed API response", async ({ page }) => {
    await page.goto("/jobs");

    await expectJobsApiBannerCount(page, fakeJobs.jobs.length);
  });
});
