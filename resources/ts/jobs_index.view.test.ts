/** @vitest-environment happy-dom */

import { afterEach, describe, expect, it, vi } from "vitest";

import { Backbone } from "./backbone_setup";
import { bootJobsIndex } from "./jobs_index";

describe("bootJobsIndex (DOM + mocked sync)", () => {
  afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = "";
  });

  it("fills the API banner after a successful collection fetch", async () => {
    vi.spyOn(Backbone, "sync").mockImplementation((_method, _model, options) => {
      const success = (options as { success?: (resp: unknown) => void } | undefined)?.success;
      success?.({
        jobs: [
          { id: 10, employment_type: "full_time", title: "Engineer" },
          { id: 11, employment_type: "part_time", title: "Analyst" },
        ],
      });
    });

    document.body.innerHTML = `
      <section
        data-jobs-index-root
        data-api-banner-template="Count={count}"
        data-api-banner-error="err"
      >
        <p data-job-api-banner hidden><span data-job-api-banner-text></span></p>
        <select data-client-filter-type><option value="">All</option></select>
        <article class="job-card" data-employment-type="full_time"></article>
        <article class="job-card" data-employment-type="part_time"></article>
      </section>
    `;

    bootJobsIndex();

    await vi.waitFor(() => {
      const el = document.querySelector("[data-job-api-banner-text]");
      expect(el?.textContent).toBe("Count=2");
    });

    const banner = document.querySelector("[data-job-api-banner]");
    expect(banner?.hasAttribute("hidden")).toBe(false);
  });

  it("hides job cards when the client filter changes", async () => {
    vi.spyOn(Backbone, "sync").mockImplementation((_method, _model, options) => {
      const success = (options as { success?: (resp: unknown) => void } | undefined)?.success;
      success?.({ jobs: [{ id: 1, employment_type: "full_time" }] });
    });

    document.body.innerHTML = `
      <section data-jobs-index-root data-api-banner-template="" data-api-banner-error="">
        <p data-job-api-banner hidden><span data-job-api-banner-text></span></p>
        <select data-client-filter-type>
          <option value="">All</option>
          <option value="full_time">Full time</option>
          <option value="contract">Contract</option>
        </select>
        <article class="job-card" data-employment-type="full_time"></article>
        <article class="job-card" data-employment-type="contract"></article>
      </section>
    `;

    bootJobsIndex();

    await vi.waitFor(() => {
      expect(document.querySelector("[data-job-api-banner-text]")?.textContent).toContain("1");
    });

    const select = document.querySelector("[data-client-filter-type]") as HTMLSelectElement;
    select.value = "contract";
    select.dispatchEvent(new Event("change", { bubbles: true }));

    const cards = document.querySelectorAll(".job-card");
    expect((cards[0] as HTMLElement).style.display).toBe("none");
    expect((cards[1] as HTMLElement).style.display).not.toBe("none");
  });
});
