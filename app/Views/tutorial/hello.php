<?= $this->extend('tutorial/layout') ?>

<?= $this->section('content') ?>
<section class="card">
    <h2>Hello Route + Controller + View</h2>
    <p>Welcome, <strong><?= esc($name) ?></strong>!</p>
    <p>This page is rendered through custom routing and a controller action.</p>

    <h3>Topics in this mini module</h3>
    <ul>
        <?php foreach ($topics as $topic): ?>
            <li><?= esc($topic) ?></li>
        <?php endforeach; ?>
    </ul>

    <button id="greet-btn" class="btn">Run JavaScript</button>
    <p id="greet-output" class="muted"></p>
</section>
<?= $this->endSection() ?>
