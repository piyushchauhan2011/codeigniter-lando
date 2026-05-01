import { describe, expect, it } from "vitest";
import { formatPortalLocalPreview } from "./portal_clock";

describe("formatPortalLocalPreview", () => {
  it("formats a fixed instant for en", () => {
    const d = new Date(Date.UTC(2026, 4, 1, 14, 30, 0));
    const s = formatPortalLocalPreview(d, "en");
    expect(s.length).toBeGreaterThan(5);
    expect(s).toMatch(/2026/);
  });

  it("formats a fixed instant for fr", () => {
    const d = new Date(Date.UTC(2026, 4, 1, 14, 30, 0));
    const s = formatPortalLocalPreview(d, "fr");
    expect(s.length).toBeGreaterThan(5);
  });
});
