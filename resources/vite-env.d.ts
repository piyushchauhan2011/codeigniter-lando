/// <reference types="vite/client" />

/**
 * Vite + sass: side-effect stylesheet imports (no default value used at runtime).
 */
declare module "*.scss" {}

interface ImportMetaEnv {
  readonly VITE_ELASTIC_APM_ENABLED?: string;
  readonly VITE_ELASTIC_APM_SERVER_URL?: string;
  readonly VITE_ELASTIC_APM_SERVICE_NAME?: string;
  readonly VITE_ELASTIC_APM_SERVICE_VERSION?: string;
}
