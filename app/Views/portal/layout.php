<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Job Portal') ?> · CI4 Learning</title>
    <link rel="stylesheet" href="<?= esc(base_url('assets/dist/css/tutorialStyle.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/dist/css/portal.css'), 'attr') ?>">
</head>
<body>
<header class="site-header portal-header">
    <div class="portal-header-inner">
        <strong><a href="<?= site_url('jobs') ?>" class="portal-brand">Job Portal</a></strong>
        <nav class="portal-nav">
            <a href="<?= site_url('jobs') ?>">Browse jobs</a>
            <a href="<?= site_url('contact') ?>">Contact</a>
            <?php if (session()->get(\App\Libraries\PortalAuth::SESSION_USER_ID)): ?>
                <a href="<?= site_url('dashboard') ?>">Dashboard</a>
                <?php if (session()->get(\App\Libraries\PortalAuth::SESSION_ROLE) === 'employer'): ?>
                    <a href="<?= site_url('employer') ?>">Employer</a>
                <?php endif; ?>
                <?php if (session()->get(\App\Libraries\PortalAuth::SESSION_ROLE) === 'seeker'): ?>
                    <a href="<?= site_url('seeker') ?>">Seeker</a>
                <?php endif; ?>
                <form action="<?= site_url('logout') ?>" method="post" class="inline-logout">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-btn">Sign out</button>
                </form>
            <?php else: ?>
                <a href="<?= site_url('login') ?>">Sign in</a>
                <a href="<?= site_url('register') ?>">Register</a>
            <?php endif; ?>
            <a href="<?= site_url('/') ?>" class="muted-link">Home</a>
        </nav>
    </div>
</header>

<main class="container portal-main">
    <?php if (session()->getFlashdata('message')): ?>
        <p class="flash-success"><?= esc(session()->getFlashdata('message')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <p class="flash-error"><?= esc(session()->getFlashdata('error')) ?></p>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>
</main>

<script src="<?= esc(base_url('assets/dist/js/tutorial.js'), 'attr') ?>"></script>
</body>
</html>
