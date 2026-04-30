<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'CodeIgniter Tutorial') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/tutorial.css') ?>">
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

    <script src="<?= base_url('assets/tutorial.js') ?>"></script>
</body>
</html>
