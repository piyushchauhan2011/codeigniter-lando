<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card">
    <h2><?= esc($title) ?></h2>
    <p><a href="<?= portal_url('employer') ?>">← Back to dashboard</a></p>

    <?php if ($applications === []): ?>
        <p>No applicants yet.</p>
    <?php else: ?>
        <table class="portal-table">
            <thead>
            <tr>
                <th>Applicant</th>
                <th>Applied</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= esc($app['seeker_email']) ?><br><span class="portal-text-muted"><?= esc($app['seeker_headline'] ?? '') ?></span></td>
                    <td><?= esc($app['created_at'] ?? '') ?></td>
                    <td>
                        <form method="post" action="<?= portal_url('employer/applications/' . (int) $app['id'] . '/status') ?>" class="portal-inline-status">
                            <?= csrf_field() ?>
                            <select name="status">
                                <?php foreach (['submitted', 'shortlisted', 'rejected'] as $st): ?>
                                    <option value="<?= esc($st, 'attr') ?>" <?= ($app['status'] ?? '') === $st ? 'selected' : '' ?>><?= esc($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="portal-button portal-button--secondary portal-button--small">Update</button>
                        </form>
                    </td>
                    <td>
                        <?php if (! empty($app['resume_path'])): ?>
                            <a href="<?= portal_url('employer/applications/' . (int) $app['id'] . '/resume') ?>">Resume</a>
                        <?php else: ?>
                            <span class="portal-text-muted">No file</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="portal-table__row--cover">
                    <td colspan="4"><strong>Cover letter</strong><div class="applications-table__cover-letter"><?= nl2br(esc($app['cover_letter'])) ?></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
