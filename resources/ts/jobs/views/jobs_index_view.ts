import _ from "underscore";

import { isFeatureEnabled } from "../../feature_flags";
import { $, Backbone } from "../../backbone_setup";

import { JobsCollection } from "../collections/jobs";
import { jobCardMatchesFilter } from "../core";

type JobsCollectionInstance = InstanceType<typeof JobsCollection>;
type JobsIndexViewType = Backbone.View & {
  collection: JobsCollectionInstance;
  onApiSync(): void;
  onApiError(): void;
  onFilterChange(): void;
};

function filterJobCards($root: JQuery, employmentType: string): void {
  $root.find(".job-card").each(function (this: HTMLElement) {
    const $card = $(this);
    const t = String($card.attr("data-employment-type") ?? "");
    const show = jobCardMatchesFilter(t, employmentType);
    $card.toggle(show);
  });
}

export const JobsIndexView = Backbone.View.extend({
  events: {
    "change [data-client-filter-type]": "onFilterChange",
  },
  collection: undefined as unknown as JobsCollectionInstance,

  initialize(this: JobsIndexViewType) {
    const Ctor = JobsCollection as unknown as { new (): JobsCollectionInstance };
    this.collection = new Ctor();
    if (!isFeatureEnabled("jobsApiLiveBanner")) {
      return;
    }
    this.listenTo(this.collection, "sync", this.onApiSync);
    this.listenTo(this.collection, "error", this.onApiError);
    this.collection.fetch();
  },

  onApiSync(this: JobsIndexViewType) {
    const $banner = this.$("[data-job-api-banner]");
    const $text = this.$("[data-job-api-banner-text]");
    const template = String(this.$el.attr("data-api-banner-template") ?? "");
    const n = _.size(this.collection.models);
    if (template !== "") {
      $text.text(template.replaceAll("{count}", String(n)));
    } else {
      $text.text(`${n} published opening(s) reported by the API.`);
    }
    $banner.prop("hidden", false);
  },

  onApiError(this: JobsIndexViewType) {
    const $banner = this.$("[data-job-api-banner]");
    const $text = this.$("[data-job-api-banner-text]");
    const errTpl = String(
      this.$el.attr("data-api-banner-error") ?? "Could not load live job count.",
    );
    $text.text(errTpl);
    $banner.prop("hidden", false);
  },

  onFilterChange(this: JobsIndexViewType) {
    const raw = this.$("[data-client-filter-type]").val();
    const v = typeof raw === "string" ? raw : "";
    filterJobCards(this.$el, v);
  },
}) as unknown as typeof Backbone.View;
