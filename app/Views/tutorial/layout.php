<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'CodeIgniter Tutorial') ?></title>
    <?php
    // Root-relative URLs so CSS/JS work when hostname differs from app.baseURL
    // (e.g. GitHub Actions + Lando: page is *.lndo.site but default baseURL may be localhost).
    ?>
    <link rel="stylesheet" href="<?= esc('/assets/dist/css/tutorialStyle.css', 'attr') ?>">
</head>
<body>
    <header class="site-header">
        <h1>CodeIgniter Learning Playground</h1>
        <nav>
            <a href="<?= site_url('/hello') ?>">Hello</a>
            <a href="<?= site_url('/posts') ?>">Posts</a>
            <a href="<?= site_url('/posts/new') ?>">Create Post</a>
        </nav>
    </header>

    <main class="container">
        <?= $this->renderSection('content') ?>
    </main>

    <script src="<?= esc('/assets/dist/js/tutorial.js', 'attr') ?>"></script>
</body>
</html>
