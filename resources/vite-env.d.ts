/// <reference types="vite/client" />

/**
 * Vite + sass: side-effect stylesheet imports (no default value used at runtime).
 */
declare module "*.scss" {}

interface ImportMetaEnv {
  readonly VITE_GLITCHTIP_DSN?: string;
  readonly VITE_GLITCHTIP_RELEASE?: string;
  /** 0–1, default 0 in dev and 0.01 in production builds (GlitchTip recommends a low rate). */
  readonly VITE_GLITCHTIP_TRACES_SAMPLE_RATE?: string;
  /** Same-origin path for Sentry tunnel → GlitchTip ingest proxy (CodeIgniter). */
  readonly VITE_GLITCHTIP_TUNNEL_PATH?: string;
}
