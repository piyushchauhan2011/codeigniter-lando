<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card">
    <h2><?= esc($title) ?></h2>
    <p><a class="btn" href="<?= portal_url('seeker/profile') ?>">Edit profile</a>
        <a class="btn secondary" href="<?= portal_url('seeker/applications') ?>">Applications</a></p>

    <?php if ($applications === []): ?>
        <p>No applications yet.</p>
    <?php else: ?>
        <ul class="portal-list">
            <?php foreach ($applications as $app): ?>
                <li><strong><?= esc($app['job_title']) ?></strong> — <?= esc($app['status']) ?> · <?= esc($app['created_at'] ?? '') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
