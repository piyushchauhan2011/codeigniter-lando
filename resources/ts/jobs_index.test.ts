import { describe, expect, it } from "vitest";

import { jobCardMatchesFilter, parseJobsApiPayload } from "./jobs_index_core";

describe("parseJobsApiPayload", () => {
  it("returns jobs from a valid API envelope", () => {
    const rows = parseJobsApiPayload({
      jobs: [
        { id: 1, employment_type: "full_time", title: "Dev" },
        { id: "2", title: "Ops" },
      ],
    });
    expect(rows).toEqual([
      {
        id: 1,
        employment_type: "full_time",
        title: "Dev",
        location: undefined,
        company_name: undefined,
      },
      {
        id: 2,
        employment_type: undefined,
        title: "Ops",
        location: undefined,
        company_name: undefined,
      },
    ]);
  });

  it("returns an empty array for invalid payloads", () => {
    expect(parseJobsApiPayload(null)).toEqual([]);
    expect(parseJobsApiPayload({})).toEqual([]);
    expect(parseJobsApiPayload({ jobs: "no" })).toEqual([]);
    expect(parseJobsApiPayload({ jobs: [{}] })).toEqual([]);
  });
});

describe("jobCardMatchesFilter", () => {
  it("matches when filter is empty", () => {
    expect(jobCardMatchesFilter("full_time", "")).toBe(true);
  });

  it("matches exact employment type", () => {
    expect(jobCardMatchesFilter("contract", "contract")).toBe(true);
    expect(jobCardMatchesFilter("contract", "full_time")).toBe(false);
  });
});
