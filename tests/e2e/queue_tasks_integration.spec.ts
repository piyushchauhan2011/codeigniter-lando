import path from "node:path";

import { expect, test } from "@playwright/test";

/**
 * Full-stack check: demo seeker submits an application (fires Events → queue push).
 * CI runs `queue:work` after this spec to process `notify-employer-application`.
 *
 * Note: Demo seed does not set seeker resume_path; Seeker::apply requires a resume
 * (profile or upload). We attach a minimal PDF via the apply form.
 */
test.describe("Queue trigger via portal", () => {
  test("seeker signs in and applies to seeded job", async ({ page }) => {
    const resumePdf = path.join(process.cwd(), "tests/e2e/fixtures/ci-resume.pdf");

    await page.goto("/login");
    await page.locator("#email").fill("seeker@example.test");
    await page.locator("#password").fill("password123");
    await page.getByRole("button", { name: "Sign in" }).click();

    await expect(page).toHaveURL(/seeker/);

    await page.goto("/jobs/1");
    await expect(page.getByRole("heading", { name: "Senior PHP Engineer" })).toBeVisible();

    await page.locator("#cover_letter").fill(
      "I am excited about this role and bring solid PHP experience for your team.",
    );
    await page.locator("#resume").setInputFiles(resumePdf);
    await page.getByRole("button", { name: "Submit application" }).click();

    // Session flash is a direct child of <main>. After apply, the job view also shows a nested
    // `.portal-flash--success` ("already applied"), so a bare `.portal-flash--success` matches twice.
    await expect(
      page.locator("main.portal-page__main > .portal-flash.portal-flash--success"),
    ).toContainText("Application submitted.");
  });
});
