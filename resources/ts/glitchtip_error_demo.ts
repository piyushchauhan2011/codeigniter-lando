import * as Sentry from "@sentry/browser";

export function initGlitchTipErrorDemo(): void {
  const button = document.querySelector<HTMLButtonElement>("[data-portal-error-demo]");
  if (!button) {
    return;
  }

  button.addEventListener("click", () => {
    const jobId = 42;
    const error = new Error(`ELK lab browser demo error for job ${jobId}`);
    const client = Sentry.getClient();
    if (client) {
      Sentry.captureException(error);
      console.info(
        "[ELK lab] Demo error sent to GlitchTip (Sentry-compatible SDK). Open Issues in GlitchTip.",
        error.message,
      );
    } else {
      console.warn(
        "[ELK lab] GlitchTip not initialized — capture skipped. Put VITE_GLITCHTIP_DSN in a root `.env` (see `.env.example` and docs/GLITCHTIP_LANDO.md), then run `pnpm build` so the token is baked into portal.js.",
      );
    }
  });
}
