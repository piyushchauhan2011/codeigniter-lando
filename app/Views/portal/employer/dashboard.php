<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card">
    <h2><?= esc($title) ?></h2>
    <p><a class="btn" href="<?= portal_url('employer/jobs/new') ?>">Post a job</a>
        <a class="btn secondary" href="<?= portal_url('employer/profile') ?>">Company profile</a></p>

    <?php if ($jobs === []): ?>
        <p>No jobs yet.</p>
    <?php else: ?>
        <table class="portal-table">
            <thead><tr><th>Title</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($jobs as $job): ?>
                <tr>
                    <td><?= esc($job['title']) ?></td>
                    <td><?= esc($job['status']) ?></td>
                    <td class="actions">
                        <a href="<?= portal_url('jobs/' . (int) $job['id']) ?>">View</a>
                        <a href="<?= portal_url('employer/jobs/' . (int) $job['id'] . '/edit') ?>">Edit</a>
                        <a href="<?= portal_url('employer/jobs/' . (int) $job['id'] . '/applications') ?>">Applicants</a>
                        <form method="post" action="<?= portal_url('employer/jobs/' . (int) $job['id'] . '/delete') ?>" onsubmit="return confirm('Delete this job?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="link-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
