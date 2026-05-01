import "../scss/portal.scss";

function initLocalClock(): void {
  const root = document.documentElement;
  const locale = root.dataset.locale ?? "en";
  const wrap = document.getElementById("portal-local-time");
  const el = document.querySelector("[data-portal-local-clock]");
  if (!wrap || !el) {
    return;
  }

  const fmt = new Intl.DateTimeFormat(locale, {
    dateStyle: "medium",
    timeStyle: "short",
  });

  const tick = (): void => {
    el.textContent = fmt.format(new Date());
  };

  tick();
  wrap.hidden = false;
  window.setInterval(tick, 30_000);
}

export function formatPortalLocalPreview(date: Date, locale: string): string {
  return new Intl.DateTimeFormat(locale, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date);
}

if (typeof document !== "undefined") {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initLocalClock);
  } else {
    initLocalClock();
  }
}
