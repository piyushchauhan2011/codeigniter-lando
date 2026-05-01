<article class="job-card">
    <h3><a href="<?= site_url('jobs/' . (int) ($job['id'] ?? 0)) ?>"><?= esc($job['title'] ?? '') ?></a></h3>
    <p class="muted">
        <?= esc($job['company_name'] ?? 'Company') ?> · <?= esc($job['location'] ?? '') ?> · <?= esc(str_replace('_', '-', (string) ($job['employment_type'] ?? ''))) ?>
    </p>
    <?php if (! empty($job['salary_min']) || ! empty($job['salary_max'])): ?>
        <p class="salary">
            <?php if (! empty($job['salary_min'])): ?>From <?= esc((string) $job['salary_min']) ?><?php endif; ?>
            <?php if (! empty($job['salary_max'])): ?> — up to <?= esc((string) $job['salary_max']) ?><?php endif; ?>
        </p>
    <?php endif; ?>
</article>
