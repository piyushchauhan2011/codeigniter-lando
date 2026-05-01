import { Backbone } from "../../backbone_setup";

import { JobModel } from "../models/job";
import { parseJobsApiPayload } from "../core";

export const JobsCollection = Backbone.Collection.extend({
  model: JobModel,
  url: "/api/jobs",
  parse(response: unknown): unknown[] {
    return parseJobsApiPayload(response).map((j) => ({ ...j }));
  },
});
