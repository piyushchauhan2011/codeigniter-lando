import { $, Backbone } from "../backbone_setup";

import { JobsIndexView } from "./views/jobs_index_view";

export type { PortalJobRow } from "./core";
export { jobCardMatchesFilter, parseJobsApiPayload } from "./core";

export function bootJobsIndex(): void {
  const $root = $("[data-jobs-index-root]");
  if ($root.length === 0) {
    return;
  }
  const V = JobsIndexView as unknown as new (opts: { el: HTMLElement }) => Backbone.View;
  const el = $root.get(0);
  if (!el) {
    return;
  }
  new V({ el });
}
