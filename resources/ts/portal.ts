import "../scss/portal.scss";

import { bootJobsIndex } from "./jobs";
import { initGlitchTip } from "./glitchtip";
import { initGlitchTipErrorDemo } from "./glitchtip_error_demo";
import { initLocalClock } from "./portal_clock";

export { formatPortalLocalPreview } from "./portal_clock";

function initPortal(): void {
  initGlitchTip();
  initLocalClock();
  bootJobsIndex();
  initGlitchTipErrorDemo();
}

if (typeof document !== "undefined") {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPortal);
  } else {
    initPortal();
  }
}
