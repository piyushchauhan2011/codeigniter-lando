<article
    class="job-card"
    data-job-id="<?= (int) ($job['id'] ?? 0) ?>"
    data-employment-type="<?= esc((string) ($job['employment_type'] ?? ''), 'attr') ?>"
>
    <h3 class="job-card__title">
        <a href="<?= portal_url('jobs/' . (int) ($job['id'] ?? 0)) ?>"><?= esc($job['title'] ?? '') ?></a>
        <?php if (isset($job['is_featured']) && (int) $job['is_featured'] === 1): ?>
            <span class="portal-text-muted">Featured</span>
        <?php endif; ?>
    </h3>
    <p class="job-card__meta portal-text-muted">
        <?= esc($job['company_name'] ?? 'Company') ?> · <?= esc($job['location'] ?? '') ?> · <?= esc(portal_employment_label((string) ($job['employment_type'] ?? ''))) ?>
    </p>
    <?php
        $salaryMin = isset($job['salary_min']) ? (string) $job['salary_min'] : '';
        $salaryMax = isset($job['salary_max']) ? (string) $job['salary_max'] : '';
        ?>
    <?php if ($salaryMin !== '' || $salaryMax !== ''): ?>
        <p class="job-card__salary">
            <span class="job-card__salary-label"><?= esc(lang('Portal.salary_label')) ?>:</span>
            <?php if ($salaryMin !== ''): ?><?= esc(lang('Portal.salary_from')) ?> <?= esc($salaryMin) ?><?php endif; ?>
            <?php if ($salaryMax !== ''): ?> — <?= esc(lang('Portal.salary_up_to')) ?> <?= esc($salaryMax) ?><?php endif; ?>
        </p>
    <?php endif; ?>
    <?php if (isset($job['created_at']) && is_string($job['created_at']) && $job['created_at'] !== ''): ?>
        <p class="job-card__posted portal-text-muted"><span class="job-card__posted-label"><?= esc(lang('Portal.posted_label')) ?>:</span> <?= esc(portal_localized_datetime($job['created_at'])) ?></p>
    <?php endif; ?>
</article>
