<?php if ($jobs !== []): ?>
    <aside class="portal-card portal-card--nested" aria-labelledby="featured-jobs-cell-heading">
        <h3 id="featured-jobs-cell-heading"><?= esc(lang('FeaturedJobs.cell_heading')) ?></h3>
        <p class="portal-text-muted"><?= esc(lang('FeaturedJobs.cell_intro')) ?></p>

        <ul class="portal-list">
            <?php foreach ($jobs as $job): ?>
                <li>
                    <a href="<?= portal_url('jobs/' . (int) ($job['id'] ?? 0)) ?>"><?= esc($job['title'] ?? '') ?></a>
                    <span class="portal-text-muted">
                        <?= esc($job['company_name'] ?? lang('FeaturedJobs.company_fallback')) ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>

        <p><a href="<?= esc($learningUrl, 'attr') ?>"><?= esc(lang('FeaturedJobs.learn_link')) ?></a></p>
    </aside>
<?php endif; ?>
