<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card">
    <h2><?= esc($title) ?></h2>

    <?php if ($applications === []): ?>
        <p><?= esc(lang('Portal.applications_empty')) ?> <a href="<?= portal_url('jobs') ?>"><?= esc(lang('Portal.applications_browse')) ?></a>.</p>
    <?php else: ?>
        <table class="portal-table">
            <thead><tr><th><?= esc(lang('Portal.table_job')) ?></th><th><?= esc(lang('Portal.table_location')) ?></th><th><?= esc(lang('Portal.table_status')) ?></th><th><?= esc(lang('Portal.table_applied')) ?></th></tr></thead>
            <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><a href="<?= portal_url('jobs/' . (int) $app['job_id']) ?>"><?= esc($app['job_title']) ?></a></td>
                    <td><?= esc($app['location'] ?? '') ?></td>
                    <td><?= esc($app['status']) ?></td>
                    <td><?= esc(portal_localized_datetime(isset($app['created_at']) ? (string) $app['created_at'] : null)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
