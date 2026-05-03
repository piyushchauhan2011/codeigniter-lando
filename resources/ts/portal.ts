import "../scss/portal.scss";

import { bootJobsIndex } from "./jobs";
import { initLocalClock } from "./portal_clock";

export { formatPortalLocalPreview } from "./portal_clock";

function initPortal(): void {
  initLocalClock();
  bootJobsIndex();
}

if (typeof document !== "undefined") {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPortal);
  } else {
    initPortal();
  }
}
