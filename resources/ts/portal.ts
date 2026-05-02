import "../scss/portal.scss";

import { initElasticApm } from "./elastic_apm";
import { initElkErrorDemo } from "./elk_error_demo";
import { bootJobsIndex } from "./jobs";
import { initLocalClock } from "./portal_clock";

export { formatPortalLocalPreview } from "./portal_clock";

function initPortal(): void {
  initElasticApm();
  initLocalClock();
  bootJobsIndex();
  initElkErrorDemo();
}

if (typeof document !== "undefined") {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPortal);
  } else {
    initPortal();
  }
}
