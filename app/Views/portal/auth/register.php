<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card portal-auth-card">
    <h2><?= esc($title) ?></h2>

    <?php if (! empty($errors)): ?>
        <div class="flash-error">
            <ul>
                <?php foreach ($errors as $field => $error): ?>
                    <li><?= esc(is_array($error) ? implode(' ', $error) : $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('register') ?>" class="form">
        <?= csrf_field() ?>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= esc(old('email') ?? '') ?>" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" minlength="10" required>

        <label for="password_confirm">Confirm password</label>
        <input id="password_confirm" name="password_confirm" type="password" minlength="10" required>

        <fieldset class="role-fieldset">
            <legend>Account type</legend>
            <label><input type="radio" name="role" value="seeker" <?= old('role', 'seeker') === 'seeker' ? 'checked' : '' ?>> Job seeker</label>
            <label><input type="radio" name="role" value="employer" <?= old('role') === 'employer' ? 'checked' : '' ?>> Employer</label>
        </fieldset>

        <label for="company_name">Company name (employers)</label>
        <input id="company_name" name="company_name" type="text" value="<?= esc(old('company_name') ?? '') ?>" placeholder="Acme Inc.">

        <button type="submit" class="btn">Create account</button>
    </form>
    <p class="muted">Already registered? <a href="<?= site_url('login') ?>">Sign in</a></p>
</section>
<?php $this->endSection(); ?>
