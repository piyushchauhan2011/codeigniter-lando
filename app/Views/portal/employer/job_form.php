<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card">
    <h2><?= esc($title) ?></h2>

    <?php if (! empty($errors)): ?>
        <div class="flash-error"><ul><?php foreach ($errors as $err): ?><li><?= esc(is_array($err) ? implode(' ', $err) : $err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url($job ? 'employer/jobs/' . (int) $job['id'] : 'employer/jobs') ?>" class="form">
        <?= csrf_field() ?>
        <label for="title">Title</label>
        <input id="title" name="title" value="<?= esc(old('title', $job['title'] ?? '')) ?>" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="10" required><?= esc(old('description', $job['description'] ?? '')) ?></textarea>

        <label for="employment_type">Employment type</label>
        <select id="employment_type" name="employment_type">
            <?php foreach (['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract'] as $val => $label): ?>
                <option value="<?= esc($val, 'attr') ?>" <?= old('employment_type', $job['employment_type'] ?? 'full_time') === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="location">Location</label>
        <input id="location" name="location" value="<?= esc(old('location', $job['location'] ?? '')) ?>" required>

        <label for="salary_min">Salary min (optional)</label>
        <input id="salary_min" name="salary_min" type="number" min="0" value="<?= esc(old('salary_min', isset($job['salary_min']) ? (string) $job['salary_min'] : '')) ?>">

        <label for="salary_max">Salary max (optional)</label>
        <input id="salary_max" name="salary_max" type="number" min="0" value="<?= esc(old('salary_max', isset($job['salary_max']) ? (string) $job['salary_max'] : '')) ?>">

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
            <option value="">—</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= esc((string) $cat['id'], 'attr') ?>" <?= ((string) old('category_id', isset($job['category_id']) ? (string) $job['category_id'] : '') === (string) $cat['id']) ? 'selected' : '' ?>><?= esc($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="status">Status</label>
        <select id="status" name="status">
            <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $val => $label): ?>
                <option value="<?= esc($val, 'attr') ?>" <?= old('status', $job['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn"><?= $job ? 'Update job' : 'Create job' ?></button>
    </form>
</section>
<?php $this->endSection(); ?>
