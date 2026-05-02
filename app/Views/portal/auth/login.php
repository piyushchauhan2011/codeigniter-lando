<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card portal-card--auth">
    <h2><?= esc($title) ?></h2>

    <?php if (! empty($errors)): ?>
        <div class="portal-flash portal-flash--error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= esc(is_array($error) ? implode(' ', $error) : $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= portal_url('login') ?>" class="portal-form">
        <?= csrf_field() ?>
        <label for="email"><?= esc(lang('Portal.auth_email')) ?></label>
        <input id="email" name="email" type="email" value="<?= esc(old('email') ?? '') ?>" required>

        <label for="password"><?= esc(lang('Portal.auth_password')) ?></label>
        <input id="password" name="password" type="password" required>

        <label>
            <input name="remember" type="checkbox" value="1" <?= old('remember') ? 'checked' : '' ?>>
            Remember me on this device
        </label>

        <button type="submit" class="portal-button"><?= esc(lang('Portal.auth_submit_sign_in')) ?></button>
    </form>
    <p class="portal-text-muted"><?= esc(lang('Portal.auth_no_account')) ?> <a href="<?= portal_url('register') ?>"><?= esc(lang('Portal.nav_register')) ?></a></p>
</section>
<?php $this->endSection(); ?>
