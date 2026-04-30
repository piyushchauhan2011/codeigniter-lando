import { describe, expect, it } from "vitest";
import { buildGreeting } from "./tutorial";

describe("buildGreeting", () => {
  it("returns the tutorial greeting prefix", () => {
    const greeting = buildGreeting(new Date("2026-04-30T10:00:00Z"));

    expect(greeting).toContain("JavaScript is working! Time:");
  });
});
