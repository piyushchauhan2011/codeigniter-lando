import { expect, test } from "@playwright/test";

test("hello page renders tutorial content", async ({ page }) => {
  await page.goto("/hello");

  await expect(page.getByRole("heading", { name: "Hello Route + Controller + View" })).toBeVisible();
  await page.getByRole("button", { name: "Run JavaScript" }).click();
  await expect(page.locator("#greet-output")).toContainText("JavaScript is working! Time:");
});
