<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card">
    <article class="job-detail">
        <h2><?= esc($job['title']) ?></h2>
        <p class="muted">
            <?= esc($job['company_name'] ?? '') ?> · <?= esc($job['location']) ?> · <?= esc(str_replace('_', '-', (string) ($job['employment_type'] ?? ''))) ?>
            <?php if (! empty($job['salary_min']) || ! empty($job['salary_max'])): ?>
                · Salary:
                <?php if (! empty($job['salary_min'])): ?><?= esc((string) $job['salary_min']) ?><?php endif; ?>
                <?php if (! empty($job['salary_max'])): ?>–<?= esc((string) $job['salary_max']) ?><?php endif; ?>
            <?php endif; ?>
        </p>
        <div class="job-body"><?= nl2br(esc($job['description'])) ?></div>
    </article>

    <?php if (session()->get(\App\Libraries\PortalAuth::SESSION_ROLE) === 'seeker'): ?>
        <section class="card nested-card">
            <h3>Actions</h3>
            <form method="post" action="<?= site_url('seeker/jobs/' . (int) $job['id'] . '/save') ?>" class="inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn secondary"><?= $saved ? 'Unsave job' : 'Save job' ?></button>
            </form>

            <?php if ($canApply): ?>
                <h4>Apply</h4>
                <?php if (! empty($errors)): ?>
                    <div class="flash-error"><ul><?php foreach ($errors as $err): ?><li><?= esc(is_array($err) ? implode(' ', $err) : $err) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="post" action="<?= site_url('seeker/jobs/' . (int) $job['id'] . '/apply') ?>" class="form" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label for="cover_letter">Cover letter</label>
                    <textarea id="cover_letter" name="cover_letter" rows="6" required><?= esc(old('cover_letter') ?? '') ?></textarea>
                    <label for="resume">Resume override (PDF/DOC, optional)</label>
                    <input id="resume" name="resume" type="file">
                    <button type="submit" class="btn">Submit application</button>
                </form>
            <?php elseif ($applied): ?>
                <p class="flash-success">You have already applied to this role.</p>
            <?php endif; ?>
        </section>
    <?php elseif (! session()->get(\App\Libraries\PortalAuth::SESSION_USER_ID)): ?>
        <p><a href="<?= site_url('login') ?>">Sign in</a> as a job seeker to apply or save jobs.</p>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
