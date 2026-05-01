<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section
    class="card"
    data-jobs-index-root
    data-api-banner-template="<?= esc(lang('Portal.api_jobs_banner'), 'attr') ?>"
    data-api-banner-error="<?= esc(lang('Portal.api_jobs_banner_error'), 'attr') ?>"
>
    <h2><?= esc($title) ?></h2>
    <p class="muted"><?= esc(lang('Portal.filters_bookmark_hint')) ?></p>
    <p class="job-api-banner muted" data-job-api-banner hidden>
        <span data-job-api-banner-text></span>
    </p>

    <form method="get" action="<?= portal_url('jobs') ?>" class="job-search-form">
        <label><?= esc(lang('Portal.filter_keywords')) ?>
            <input type="text" name="q" value="<?= esc((string) ($filters['q'] ?? '')) ?>" placeholder="<?= esc(lang('Portal.placeholder_keywords'), 'attr') ?>">
        </label>
        <label><?= esc(lang('Portal.filter_location')) ?>
            <input type="text" name="location" value="<?= esc((string) ($filters['location'] ?? '')) ?>" placeholder="<?= esc(lang('Portal.placeholder_location'), 'attr') ?>">
        </label>
        <label><?= esc(lang('Portal.filter_type')) ?>
            <select name="employment_type">
                <option value=""><?= esc(lang('Portal.filter_any')) ?></option>
                <?php
                $types = ['full_time', 'part_time', 'contract'];
                foreach ($types as $val): ?>
                    <option value="<?= esc($val, 'attr') ?>" <?= (($filters['employment_type'] ?? '') === $val) ? 'selected' : '' ?>><?= esc(portal_employment_label($val)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><?= esc(lang('Portal.filter_category')) ?>
            <select name="category_id">
                <option value=""><?= esc(lang('Portal.filter_any')) ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= esc((string) $cat['id'], 'attr') ?>" <?= ((string) ($filters['category_id'] ?? '') === (string) $cat['id']) ? 'selected' : '' ?>><?= esc($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn"><?= esc(lang('Portal.filter_search')) ?></button>
    </form>

    <?php if ($jobs === []): ?>
        <p><?= esc(lang('Portal.jobs_none')) ?></p>
    <?php else: ?>
        <div class="job-client-toolbar">
            <label><?= esc(lang('Portal.client_filter_label')) ?>
                <select data-client-filter-type aria-label="<?= esc(lang('Portal.client_filter_aria'), 'attr') ?>">
                    <option value=""><?= esc(lang('Portal.client_filter_any')) ?></option>
                    <?php foreach (['full_time', 'part_time', 'contract'] as $val): ?>
                        <option value="<?= esc($val, 'attr') ?>"><?= esc(portal_employment_label($val)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="job-grid">
            <?php foreach ($jobs as $job): ?>
                <?= view_cell(\App\Cells\JobCardCell::class, ['job' => $job]) ?>
            <?php endforeach; ?>
        </div>
        <?= $pager->only(['q', 'location', 'employment_type', 'category_id'])->links('default', 'default_full') ?>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
