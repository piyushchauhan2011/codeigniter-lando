<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card">
    <h2><?= esc($title) ?></h2>
    <p><a href="<?= portal_url('employer') ?>">&larr; Back to dashboard</a></p>

    <p>
        Upload private learning assets for <strong><?= esc($job['title']) ?></strong>.
        They are stored in RustFS bucket <code><?= esc($storage->bucket) ?></code>, then downloaded through short-lived signed URLs.
    </p>

    <?php if ($errors !== []): ?>
        <div class="portal-flash portal-flash--error">
            <?= implode('<br>', array_map('esc', $errors)) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= portal_url('employer/jobs/' . (int) $job['id'] . '/assets') ?>" enctype="multipart/form-data" class="portal-form">
        <?= csrf_field() ?>
        <label>
            Asset file
            <input type="file" name="asset" required>
        </label>
        <p class="portal-text-muted">Allowed: images, PDF, Word docs, and text files up to 5 MB.</p>
        <button type="submit" class="portal-button">Upload to RustFS</button>
    </form>
</section>

<section class="portal-card">
    <h3>Stored assets</h3>

    <?php if ($assets === []): ?>
        <p>No assets uploaded yet.</p>
    <?php else: ?>
        <table class="portal-table">
            <thead>
            <tr>
                <th>File</th>
                <th>Object key</th>
                <th>Size</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($assets as $asset): ?>
                <tr>
                    <td>
                        <?= esc($asset['original_name']) ?><br>
                        <span class="portal-text-muted"><?= esc($asset['mime_type']) ?></span>
                    </td>
                    <td><code><?= esc($asset['object_key']) ?></code></td>
                    <td><?= number_format((int) $asset['size_bytes']) ?> bytes</td>
                    <td class="portal-table__actions">
                        <a href="<?= portal_url('employer/assets/' . (int) $asset['id'] . '/signed-url') ?>">Signed download</a>
                        <form method="post" action="<?= portal_url('employer/assets/' . (int) $asset['id'] . '/delete') ?>" onsubmit="return confirm('Delete this RustFS object?');">
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
