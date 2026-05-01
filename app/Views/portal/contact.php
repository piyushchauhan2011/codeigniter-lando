<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card">
    <h2><?= esc($title) ?></h2>
    <p class="portal-text-muted">Messages are logged and stored for demo purposes.</p>

    <?php if (! empty($errors)): ?>
        <div class="portal-flash portal-flash--error"><ul><?php foreach ($errors as $err): ?><li><?= esc(is_array($err) ? implode(' ', $err) : $err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" action="<?= portal_url('contact') ?>" class="portal-form">
        <?= csrf_field() ?>
        <label for="name">Name</label>
        <input id="name" name="name" value="<?= esc(old('name') ?? '') ?>" required>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= esc(old('email') ?? '') ?>" required>

        <label for="subject">Subject</label>
        <input id="subject" name="subject" value="<?= esc(old('subject') ?? '') ?>" required>

        <label for="body">Message</label>
        <textarea id="body" name="body" rows="8" required><?= esc(old('body') ?? '') ?></textarea>

        <button type="submit" class="portal-button">Send</button>
    </form>
</section>
<?php $this->endSection(); ?>
