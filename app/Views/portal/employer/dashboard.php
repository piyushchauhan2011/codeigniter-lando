<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card">
    <h2><?= esc($title) ?></h2>
    <p><a class="portal-button" href="<?= portal_url('employer/jobs/new') ?>">Post a job</a>
        <a class="portal-button portal-button--secondary" href="<?= portal_url('employer/profile') ?>">Company profile</a></p>

    <?php if ($jobs === []): ?>
        <p>No jobs yet.</p>
    <?php else: ?>
        <table class="portal-table">
            <thead><tr><th>Title</th><th>Status</th><th>Featured payment</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($jobs as $job): ?>
                <?php
                    $jobId         = (int) $job['id'];
                    $paymentIntent = $paymentIntents[$jobId] ?? null;
                    $paymentStatus = is_array($paymentIntent) ? (string) $paymentIntent['status'] : 'none';
                    $idempotencyKey = bin2hex(random_bytes(16));
                    ?>
                <tr>
                    <td><?= esc($job['title']) ?></td>
                    <td><?= esc($job['status']) ?></td>
                    <td>
                        <?php if (! empty($job['is_featured'])): ?>
                            <strong>Featured</strong>
                            <?php if (! empty($job['featured_until'])): ?>
                                <span class="portal-text-muted">until <?= esc(portal_localized_datetime((string) $job['featured_until'])) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="portal-text-muted">Not featured</span>
                        <?php endif; ?>

                        <?php if (is_array($paymentIntent)): ?>
                            <p class="portal-text-muted">
                                Latest payment: <a href="<?= portal_url('employer/payments/' . (int) $paymentIntent['id']) ?>"><?= esc($paymentStatus) ?></a>
                            </p>
                        <?php endif; ?>

                        <form method="post" action="<?= portal_url('employer/jobs/' . $jobId . '/feature-payment') ?>" class="portal-inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="idempotency_key" value="<?= esc($idempotencyKey, 'attr') ?>">
                            <label>
                                Fake gateway
                                <select name="scenario">
                                    <option value="success">Success</option>
                                    <option value="transient_fail">Transient fail twice, then succeed</option>
                                    <option value="permanent_fail">Permanent fail to DLQ</option>
                                    <option value="exhaust_retries">Exhaust retries to failed jobs + DLQ</option>
                                </select>
                            </label>
                            <button type="submit" class="portal-button portal-button--secondary">Feature this job ($49.99)</button>
                        </form>
                    </td>
                    <td class="portal-table__actions">
                        <a href="<?= portal_url('jobs/' . $jobId) ?>">View</a>
                        <a href="<?= portal_url('employer/jobs/' . $jobId . '/edit') ?>">Edit</a>
                        <a href="<?= portal_url('employer/jobs/' . $jobId . '/assets') ?>">Assets</a>
                        <a href="<?= portal_url('employer/jobs/' . $jobId . '/applications') ?>">Applicants</a>
                        <form method="post" action="<?= portal_url('employer/jobs/' . $jobId . '/delete') ?>" onsubmit="return confirm('Delete this job?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="portal-link-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
