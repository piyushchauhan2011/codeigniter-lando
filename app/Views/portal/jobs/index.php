<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="card">
    <h2><?= esc($title) ?></h2>
    <p class="muted">Filters use GET parameters so you can bookmark a search.</p>

    <form method="get" action="<?= site_url('jobs') ?>" class="job-search-form">
        <label>Keywords
            <input type="text" name="q" value="<?= esc((string) ($filters['q'] ?? '')) ?>" placeholder="Title or description">
        </label>
        <label>Location
            <input type="text" name="location" value="<?= esc((string) ($filters['location'] ?? '')) ?>" placeholder="City / remote">
        </label>
        <label>Type
            <select name="employment_type">
                <option value="">Any</option>
                <?php foreach (['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract'] as $val => $label): ?>
                    <option value="<?= esc($val, 'attr') ?>" <?= (($filters['employment_type'] ?? '') === $val) ? 'selected' : '' ?>><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Category
            <select name="category_id">
                <option value="">Any</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= esc((string) $cat['id'], 'attr') ?>" <?= ((string) ($filters['category_id'] ?? '') === (string) $cat['id']) ? 'selected' : '' ?>><?= esc($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn">Search</button>
    </form>

    <?php if ($jobs === []): ?>
        <p>No matching jobs.</p>
    <?php else: ?>
        <div class="job-grid">
            <?php foreach ($jobs as $job): ?>
                <?= view_cell(\App\Cells\JobCardCell::class, ['job' => $job]) ?>
            <?php endforeach; ?>
        </div>
        <?= $pager->only(['q', 'location', 'employment_type', 'category_id'])->links('default', 'default_full') ?>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
