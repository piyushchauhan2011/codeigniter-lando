<?= $this->extend('tutorial/layout') ?>

<?= $this->section('content') ?>
<section class="card">
    <h2>Create Post (Form + Validation)</h2>

    <?php if (! empty($errors)): ?>
        <div class="flash-error">
            <p>Please fix the following:</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('/posts') ?>" class="form">
        <?= csrf_field() ?>
        <label for="title">Title</label>
        <input
            id="title"
            name="title"
            type="text"
            value="<?= esc($old['title'] ?? '') ?>"
            placeholder="My first post"
            required
        >

        <label for="body">Body</label>
        <textarea
            id="body"
            name="body"
            rows="5"
            placeholder="Write at least 10 characters..."
            required
        ><?= esc($old['body'] ?? '') ?></textarea>

        <button type="submit" class="btn">Save Post</button>
    </form>
</section>
<?= $this->endSection() ?>
