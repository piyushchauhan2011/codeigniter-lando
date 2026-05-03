import * as Sentry from "@sentry/browser";

export function initSentryErrorDemo(): void {
  const button = document.querySelector<HTMLButtonElement>("[data-sentry-error-demo]");
  if (!button) {
    return;
  }

  button.addEventListener("click", () => {
    const jobId = 42;
    const error = new Error(`ELK lab browser demo error for job ${jobId}`);
    if (import.meta.env.VITE_SENTRY_DSN) {
      Sentry.captureException(error);
      console.info("[Sentry] Demo exception captured — check Issues in your Sentry project.", error.message);
    } else {
      console.warn("[Sentry] Set VITE_SENTRY_DSN (see docs/SENTRY_SELF_HOSTED.md); capture skipped.");
    }
  });
}
