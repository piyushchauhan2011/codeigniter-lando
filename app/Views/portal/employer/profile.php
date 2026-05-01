<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card">
    <h2><?= esc($title) ?></h2>

    <?php if (! empty($errors)): ?>
        <div class="flash-error"><ul><?php foreach ($errors as $err): ?><li><?= esc(is_array($err) ? implode(' ', $err) : $err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <?php if ($profile['logo_path'] ?? null): ?>
        <p><img src="<?= esc(base_url('uploads/' . $profile['logo_path']), 'attr') ?>" alt="Logo" class="company-logo"></p>
    <?php endif; ?>

    <form method="post" action="<?= portal_url('employer/profile') ?>" class="form" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label for="company_name">Company name</label>
        <input id="company_name" name="company_name" value="<?= esc(old('company_name', $profile['company_name'])) ?>" required>

        <label for="website">Website</label>
        <input id="website" name="website" value="<?= esc(old('website', $profile['website'] ?? '')) ?>">

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="6"><?= esc(old('description', $profile['description'] ?? '')) ?></textarea>

        <label for="logo">Logo (PNG/JPG/WebP, max 2MB)</label>
        <input id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp">

        <button type="submit" class="btn">Save profile</button>
    </form>
</section>
<?php $this->endSection(); ?>
