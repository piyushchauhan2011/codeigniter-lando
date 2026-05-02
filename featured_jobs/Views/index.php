<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card">
    <h2><?= esc($title) ?></h2>
    <p class="portal-text-muted"><?= esc(lang('FeaturedJobs.page_intro')) ?></p>

    <section class="portal-card portal-card--nested" aria-labelledby="featured-jobs-module-map">
        <h3 id="featured-jobs-module-map"><?= esc(lang('FeaturedJobs.map_heading')) ?></h3>
        <ul class="portal-list">
            <li><code>featured_jobs/Config/Routes.php</code> <?= esc(lang('FeaturedJobs.map_routes')) ?></li>
            <li><code>featured_jobs/Controllers/FeaturedJobs.php</code> <?= esc(lang('FeaturedJobs.map_controller')) ?></li>
            <li><code>featured_jobs/Models/FeaturedJobModel.php</code> <?= esc(lang('FeaturedJobs.map_model')) ?></li>
            <li><code>featured_jobs/Cells/FeaturedJobsCell.php</code> <?= esc(lang('FeaturedJobs.map_cell')) ?></li>
            <li><code>featured_jobs/Views/index.php</code> <?= esc(lang('FeaturedJobs.map_view')) ?></li>
        </ul>
    </section>

    <?= view_cell(\FeaturedJobs\Cells\FeaturedJobsCell::class, ['limit' => 3]) ?>

    <section class="portal-card portal-card--nested" aria-labelledby="featured-jobs-page-data">
        <h3 id="featured-jobs-page-data"><?= esc(lang('FeaturedJobs.page_data_heading')) ?></h3>
        <?php if ($jobs === []): ?>
            <p><?= esc(lang('FeaturedJobs.empty')) ?></p>
        <?php else: ?>
            <ul class="portal-list">
                <?php foreach ($jobs as $job): ?>
                    <li>
                        <strong><?= esc($job['title'] ?? '') ?></strong>
                        <span class="portal-text-muted">
                            <?= esc($job['company_name'] ?? lang('FeaturedJobs.company_fallback')) ?>
                            <?php if (! empty($job['category_name'])): ?>
                                · <?= esc($job['category_name']) ?>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</section>
<?php $this->endSection(); ?>
