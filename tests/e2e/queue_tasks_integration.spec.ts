import { expect, test } from "@playwright/test";

/**
 * Full-stack check: demo seeker submits an application (fires Events → queue push).
 * CI runs `queue:work` after this spec to process `notify-employer-application`.
 */
test.describe("Queue trigger via portal", () => {
  test("seeker signs in and applies to seeded job", async ({ page }) => {
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
    await page.getByRole("button", { name: "Submit application" }).click();

    await expect(page.getByText("Application submitted.", { exact: true })).toBeVisible();
  });
});
