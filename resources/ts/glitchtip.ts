import * as Sentry from "@sentry/browser";

/**
 * GlitchTip is Sentry-protocol compatible. Options follow GlitchTip’s project onboarding
 * (low performance sample rate; avoid session traffic GlitchTip does not support).
 */
export function initGlitchTip(): void {
  const dsn = import.meta.env.VITE_GLITCHTIP_DSN;
  if (!dsn || typeof dsn !== "string" || dsn.trim() === "") {
    return;
  }

  const release = import.meta.env.VITE_GLITCHTIP_RELEASE;
  const trimmedRelease =
    release && typeof release === "string" && release.trim() !== "" ? release.trim() : undefined;

  const tracesRaw = import.meta.env.VITE_GLITCHTIP_TRACES_SAMPLE_RATE;
  let tracesSampleRate = import.meta.env.DEV ? 0 : 0.01;
  if (tracesRaw !== undefined && tracesRaw !== "") {
    const parsed = Number.parseFloat(tracesRaw);
    if (!Number.isNaN(parsed)) {
      tracesSampleRate = parsed;
    }
  }

  const tunnelRaw = import.meta.env.VITE_GLITCHTIP_TUNNEL_PATH;
  let tunnel: string | undefined;
  if (tunnelRaw === "") {
    tunnel = undefined;
  } else if (tunnelRaw !== undefined && typeof tunnelRaw === "string") {
    const t = tunnelRaw.trim();
    tunnel = t === "" ? undefined : t.startsWith("/") ? t : `/${t}`;
  } else {
    tunnel = "/learning/elk/glitchtip-tunnel";
  }

  Sentry.init({
    dsn: dsn.trim(),
    ...(tunnel !== undefined ? { tunnel } : {}),
    environment: import.meta.env.MODE,
    tracesSampleRate,
    ...(trimmedRelease !== undefined ? { release: trimmedRelease } : {}),
  });
}
