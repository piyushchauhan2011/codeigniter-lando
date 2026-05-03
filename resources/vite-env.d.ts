/// <reference types="vite/client" />

/**
 * Vite + sass: side-effect stylesheet imports (no default value used at runtime).
 */
declare module "*.scss" {}

interface ImportMetaEnv {
  readonly VITE_SENTRY_DSN?: string;
  readonly VITE_SENTRY_ENVIRONMENT?: string;
  readonly VITE_SENTRY_RELEASE?: string;
  readonly VITE_SENTRY_TRACES_SAMPLE_RATE?: string;
}
