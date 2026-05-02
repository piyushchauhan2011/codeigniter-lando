<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<?php $auth = \Config\Services::portalAuth(); ?>
<section class="portal-card">
    <article class="job-detail">
        <h2><?= esc($job['title']) ?></h2>
        <p class="job-detail__summary portal-text-muted">
            <?= esc($job['company_name'] ?? '') ?> · <?= esc($job['location']) ?> · <?= esc(portal_employment_label((string) ($job['employment_type'] ?? ''))) ?>
            <?php if (! empty($job['salary_min']) || ! empty($job['salary_max'])): ?>
                · <?= esc(lang('Portal.salary_label')) ?>:
                <?php if (! empty($job['salary_min'])): ?><?= esc(lang('Portal.salary_from')) ?> <?= esc((string) $job['salary_min']) ?><?php endif; ?>
                <?php if (! empty($job['salary_max'])): ?> — <?= esc(lang('Portal.salary_up_to')) ?> <?= esc((string) $job['salary_max']) ?><?php endif; ?>
            <?php endif; ?>
        </p>
        <?php if (! empty($job['created_at'])): ?>
            <p class="job-detail__posted portal-text-muted"><span class="job-detail__posted-label"><?= esc(lang('Portal.posted_label')) ?>:</span> <?= esc(portal_localized_datetime((string) $job['created_at'])) ?></p>
        <?php endif; ?>
        <div class="job-detail__body"><?= nl2br(esc($job['description'])) ?></div>
    </article>

    <?php if ($auth->isSeeker()): ?>
        <section class="portal-card portal-card--nested">
            <h3>Actions</h3>
            <form method="post" action="<?= portal_url('seeker/jobs/' . (int) $job['id'] . '/save') ?>" class="portal-inline-form">
                <?= csrf_field() ?>
                <button type="submit" class="portal-button portal-button--secondary"><?= $saved ? 'Unsave job' : 'Save job' ?></button>
            </form>

            <?php if ($canApply): ?>
                <h4>Apply</h4>
                <?php if (! empty($errors)): ?>
                    <div class="portal-flash portal-flash--error"><ul><?php foreach ($errors as $err): ?><li><?= esc(is_array($err) ? implode(' ', $err) : $err) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="post" action="<?= portal_url('seeker/jobs/' . (int) $job['id'] . '/apply') ?>" class="portal-form" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label for="cover_letter">Cover letter</label>
                    <textarea id="cover_letter" name="cover_letter" rows="6" required><?= esc(old('cover_letter') ?? '') ?></textarea>
                    <label for="resume">Resume override (PDF/DOC, optional)</label>
                    <input id="resume" name="resume" type="file">
                    <button type="submit" class="portal-button">Submit application</button>
                </form>
            <?php elseif ($applied): ?>
                <p class="portal-flash portal-flash--success">You have already applied to this role.</p>
            <?php endif; ?>
        </section>
    <?php elseif (! $auth->check()): ?>
        <p><a href="<?= portal_url('login') ?>">Sign in</a> as a job seeker to apply or save jobs.</p>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
