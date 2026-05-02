import { getElasticApm } from "./elastic_apm";

export function initElkErrorDemo(): void {
  const button = document.querySelector<HTMLButtonElement>("[data-elk-js-error-demo]");
  if (!button) {
    return;
  }

  button.addEventListener("click", () => {
    const jobId = 42;
    const error = new Error(`ELK lab browser demo error for job ${jobId}`);
    const apm = getElasticApm();
    if (apm) {
      apm.captureError(error);
      console.info(
        "[ELK lab] Demo error sent to Elastic APM (no uncaught exception by design). Check Network → __apm-proxy intake, then Kibana → APM → Errors.",
        error.message,
      );
    } else {
      console.warn(
        "[ELK lab] RUM agent not initialized — capture skipped. Ensure `pnpm build` includes elastic_apm init and `VITE_ELASTIC_APM_ENABLED` is not \"0\".",
      );
    }
  });
}
