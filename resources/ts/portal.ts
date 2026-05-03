import "../scss/portal.scss";

import { bootJobsIndex } from "./jobs";
import { initLocalClock } from "./portal_clock";
import { initSentry } from "./sentry";
import { initSentryErrorDemo } from "./sentry_error_demo";

export { formatPortalLocalPreview } from "./portal_clock";

function initPortal(): void {
  initSentry();
  initLocalClock();
  bootJobsIndex();
  initSentryErrorDemo();
}

if (typeof document !== "undefined") {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPortal);
  } else {
    initPortal();
  }
}
