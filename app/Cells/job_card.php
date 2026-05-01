<article class="job-card">
    <h3><a href="<?= portal_url('jobs/' . (int) ($job['id'] ?? 0)) ?>"><?= esc($job['title'] ?? '') ?></a></h3>
    <p class="muted">
        <?= esc($job['company_name'] ?? 'Company') ?> · <?= esc($job['location'] ?? '') ?> · <?= esc(portal_employment_label((string) ($job['employment_type'] ?? ''))) ?>
    </p>
    <?php
        $salaryMin = isset($job['salary_min']) ? (string) $job['salary_min'] : '';
        $salaryMax = isset($job['salary_max']) ? (string) $job['salary_max'] : '';
        ?>
    <?php if ($salaryMin !== '' || $salaryMax !== ''): ?>
        <p class="salary">
            <span class="salary__label"><?= esc(lang('Portal.salary_label')) ?>:</span>
            <?php if ($salaryMin !== ''): ?><?= esc(lang('Portal.salary_from')) ?> <?= esc($salaryMin) ?><?php endif; ?>
            <?php if ($salaryMax !== ''): ?> — <?= esc(lang('Portal.salary_up_to')) ?> <?= esc($salaryMax) ?><?php endif; ?>
        </p>
    <?php endif; ?>
    <?php if (! empty($job['created_at'])): ?>
        <p class="posted-at muted"><span class="posted-at__label"><?= esc(lang('Portal.posted_label')) ?>:</span> <?= esc(portal_localized_datetime((string) $job['created_at'])) ?></p>
    <?php endif; ?>
</article>
