<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card">
    <h2><?= esc($title) ?></h2>

    <?php if (! empty($errors)): ?>
        <div class="flash-error"><ul><?php foreach ($errors as $err): ?><li><?= esc(is_array($err) ? implode(' ', $err) : $err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <?php if (! empty($profile['resume_path'])): ?>
        <p class="muted">Resume on file (stored securely).</p>
    <?php endif; ?>

    <form method="post" action="<?= site_url('seeker/profile') ?>" class="form" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label for="headline">Headline</label>
        <input id="headline" name="headline" value="<?= esc(old('headline', $profile['headline'] ?? '')) ?>">

        <label for="bio">Bio</label>
        <textarea id="bio" name="bio" rows="6"><?= esc(old('bio', $profile['bio'] ?? '')) ?></textarea>

        <label for="skills">Skills</label>
        <textarea id="skills" name="skills" rows="4" placeholder="Comma-separated"><?= esc(old('skills', $profile['skills'] ?? '')) ?></textarea>

        <label for="resume">Resume (PDF/DOC/DOCX, max 4MB)</label>
        <input id="resume" name="resume" type="file" accept=".pdf,.doc,.docx">

        <button type="submit" class="btn">Save profile</button>
    </form>
</section>
<?php $this->endSection(); ?>
