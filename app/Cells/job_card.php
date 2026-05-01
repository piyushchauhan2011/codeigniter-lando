<article class="job-card">
    <h3><a href="<?= site_url('jobs/' . (int) ($job['id'] ?? 0)) ?>"><?= esc($job['title'] ?? '') ?></a></h3>
    <p class="muted">
        <?= esc($job['company_name'] ?? 'Company') ?> · <?= esc($job['location'] ?? '') ?> · <?= esc(str_replace('_', '-', (string) ($job['employment_type'] ?? ''))) ?>
    </p>
    <?php
        $salaryMin = isset($job['salary_min']) ? (string) $job['salary_min'] : '';
        $salaryMax = isset($job['salary_max']) ? (string) $job['salary_max'] : '';
        ?>
    <?php if ($salaryMin !== '' || $salaryMax !== ''): ?>
        <p class="salary">
            <?php if ($salaryMin !== ''): ?>From <?= esc($salaryMin) ?><?php endif; ?>
            <?php if ($salaryMax !== ''): ?> — up to <?= esc($salaryMax) ?><?php endif; ?>
        </p>
    <?php endif; ?>
</article>
