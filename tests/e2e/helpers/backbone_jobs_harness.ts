import type { Page } from "@playwright/test";

/**
 * Ensures the jobs index has a client filter select and two synthetic job cards so
 * Backbone's delegated events can run against predictable markup even when the DB
 * returns zero rows (no server-side list).
 */
export async function ensureBackboneFilterHarness(page: Page): Promise<void> {
  await page.evaluate(() => {
    const root = document.querySelector("[data-jobs-index-root]");
    if (!root) {
      throw new Error("[data-jobs-index-root] not found");
    }

    let grid = root.querySelector(".job-grid");
    if (!grid) {
      grid = document.createElement("div");
      grid.className = "job-grid";
      root.appendChild(grid);
    }

    if (!root.querySelector("[data-client-filter-type]")) {
      const toolbar = document.createElement("div");
      toolbar.className = "job-client-toolbar";
      toolbar.innerHTML = `
        <label>Filter
          <select data-client-filter-type aria-label="client filter">
            <option value="">All on this page</option>
            <option value="full_time">Full time</option>
            <option value="part_time">Part time</option>
            <option value="contract">Contract</option>
          </select>
        </label>
      `;
      root.insertBefore(toolbar, grid);
    }

    const hasHarness = grid.querySelector("[data-e2e-backbone-card]");
    if (hasHarness) {
      return;
    }

    grid.insertAdjacentHTML(
      "beforeend",
      `
<article class="job-card" data-employment-type="full_time" data-e2e-backbone-card="alpha">
  <h3>E2E Backbone Alpha</h3>
  <p class="muted">Synthetic full_time card for UI tests</p>
</article>
<article class="job-card" data-employment-type="contract" data-e2e-backbone-card="beta">
  <h3>E2E Backbone Beta</h3>
  <p class="muted">Synthetic contract card for UI tests</p>
</article>
`,
    );
  });
}
