<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card portal-auth-card">
    <h2><?= esc($title) ?></h2>

    <?php if (! empty($errors)): ?>
        <div class="flash-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= esc(is_array($error) ? implode(' ', $error) : $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('login') ?>" class="form">
        <?= csrf_field() ?>
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= esc(old('email') ?? '') ?>" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <button type="submit" class="btn">Sign in</button>
    </form>
    <p class="muted">No account? <a href="<?= site_url('register') ?>">Register</a></p>
</section>
<?php $this->endSection(); ?>
