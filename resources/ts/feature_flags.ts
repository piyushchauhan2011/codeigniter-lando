/**
 * Keys must match Config\FeatureFlags::$flags.
 */
export type PortalFeatureFlagKey = "elkLabNav" | "jobsElasticsearch" | "jobsApiLiveBanner";

export type PortalFeatureFlags = Record<PortalFeatureFlagKey, boolean>;

declare global {
  interface Window {
    __FEATURE_FLAGS__?: Partial<PortalFeatureFlags>;
  }
}

export function isFeatureEnabled(key: PortalFeatureFlagKey): boolean {
  const f = typeof window !== "undefined" ? window.__FEATURE_FLAGS__ : undefined;
  if (f === undefined) {
    return false;
  }
  const v = f[key];
  return v === true;
}
