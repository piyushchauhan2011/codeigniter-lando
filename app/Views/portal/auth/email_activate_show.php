<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card portal-card--auth">
    <h2>Verify your email</h2>

    <p>
        Shield created your account, but email verification is required before you can use the portal.
        In this learning app the verification code is shown here and also written to the CodeIgniter log.
    </p>

    <?php if (! empty($error)): ?>
        <p class="portal-flash portal-flash--error"><?= esc($error) ?></p>
    <?php endif; ?>

    <?php if (! empty($code)): ?>
        <p class="portal-flash portal-flash--success">Learning verification code: <strong><?= esc($code) ?></strong></p>
    <?php endif; ?>

    <form method="post" action="<?= url_to('auth-action-verify') ?>" class="portal-form">
        <?= csrf_field() ?>
        <label for="token">Verification code</label>
        <input id="token" name="token" type="text" inputmode="numeric" autocomplete="one-time-code" required>
        <button type="submit" class="portal-button">Verify email</button>
    </form>
</section>
<?php $this->endSection(); ?>
