<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card">
    <h2><?= esc($title) ?></h2>

    <?php if ($applications === []): ?>
        <p>You have not applied to any jobs yet. <a href="<?= site_url('jobs') ?>">Browse openings</a>.</p>
    <?php else: ?>
        <table class="portal-table">
            <thead><tr><th>Job</th><th>Location</th><th>Status</th><th>Applied</th></tr></thead>
            <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><a href="<?= site_url('jobs/' . (int) $app['job_id']) ?>"><?= esc($app['job_title']) ?></a></td>
                    <td><?= esc($app['location'] ?? '') ?></td>
                    <td><?= esc($app['status']) ?></td>
                    <td><?= esc($app['created_at'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
