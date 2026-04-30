<?= $this->extend('tutorial/layout') ?>

<?= $this->section('content') ?>
<section class="card">
    <h2>Posts (Database + Model)</h2>
    <p>This list comes from the <code>posts</code> table using <code>PostModel</code>.</p>

    <?php if (session()->getFlashdata('message')): ?>
        <p class="flash-success"><?= esc(session()->getFlashdata('message')) ?></p>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <p>No posts yet. Create your first one.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <article class="post">
                <h3><?= esc($post['title']) ?></h3>
                <p><?= esc($post['body']) ?></p>
                <small class="muted">Created: <?= esc($post['created_at'] ?? 'n/a') ?></small>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?= $this->endSection() ?>
