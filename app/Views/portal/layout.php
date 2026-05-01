<!doctype html>
<?php
$locale   = service('request')->getLocale();
$appTz    = config(\Config\App::class)->appTimezone;
$pl       = \Config\Services::portalLocale();
$isEn     = in_array($pl->getUrlPrefix(), ['', 'en'], true);
$isFr     = $pl->getUrlPrefix() === 'fr';
$urlEn    = $pl->siteUrlForLocale('en');
$urlFr    = $pl->siteUrlForLocale('fr');
?>
<html lang="<?= esc($locale, 'attr') ?>" data-locale="<?= esc($locale, 'attr') ?>" data-app-timezone="<?= esc($appTz, 'attr') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? lang('Portal.nav_brand')) ?> · <?= esc(lang('Portal.site_title_suffix')) ?></title>
    <link rel="stylesheet" href="<?= esc(base_url('assets/dist/css/tutorialStyle.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/dist/css/portal.css'), 'attr') ?>">
</head>
<body class="portal-body">
<header class="site-header portal-header">
    <div class="portal-header-inner">
        <strong><a href="<?= portal_url('jobs') ?>" class="portal-brand"><?= esc(lang('Portal.nav_brand')) ?></a></strong>
        <nav class="portal-nav" aria-label="Main">
            <a href="<?= portal_url('jobs') ?>"><?= esc(lang('Portal.nav_browse')) ?></a>
            <a href="<?= portal_url('contact') ?>"><?= esc(lang('Portal.nav_contact')) ?></a>
            <?php if (session()->get(\App\Libraries\PortalAuth::SESSION_USER_ID)): ?>
                <a href="<?= portal_url('dashboard') ?>"><?= esc(lang('Portal.nav_dashboard')) ?></a>
                <?php if (session()->get(\App\Libraries\PortalAuth::SESSION_ROLE) === 'employer'): ?>
                    <a href="<?= portal_url('employer') ?>"><?= esc(lang('Portal.nav_employer')) ?></a>
                <?php endif; ?>
                <?php if (session()->get(\App\Libraries\PortalAuth::SESSION_ROLE) === 'seeker'): ?>
                    <a href="<?= portal_url('seeker') ?>"><?= esc(lang('Portal.nav_seeker')) ?></a>
                <?php endif; ?>
                <form action="<?= portal_url('logout') ?>" method="post" class="inline-logout">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-btn"><?= esc(lang('Portal.nav_sign_out')) ?></button>
                </form>
            <?php else: ?>
                <a href="<?= portal_url('login') ?>"><?= esc(lang('Portal.nav_sign_in')) ?></a>
                <a href="<?= portal_url('register') ?>"><?= esc(lang('Portal.nav_register')) ?></a>
            <?php endif; ?>
            <a href="<?= site_url('/') ?>" class="muted-link"><?= esc(lang('Portal.nav_home')) ?></a>
            <div class="locale-switcher" role="group" aria-label="<?= esc(lang('Portal.locale_en') . ' / ' . lang('Portal.locale_fr'), 'attr') ?>">
                <a href="<?= esc($urlEn, 'attr') ?>" class="locale-switcher__btn <?= $isEn ? 'is-active' : '' ?>" hreflang="en" lang="en"><?= esc(lang('Portal.locale_en')) ?></a>
                <a href="<?= esc($urlFr, 'attr') ?>" class="locale-switcher__btn <?= $isFr ? 'is-active' : '' ?>" hreflang="fr" lang="fr"><?= esc(lang('Portal.locale_fr')) ?></a>
            </div>
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

    <p class="portal-local-time muted" id="portal-local-time" hidden>
        <span class="portal-local-time__label"><?= esc(lang('Portal.local_time_label')) ?>:</span>
        <span class="portal-local-time__value" data-portal-local-clock></span>
    </p>
</main>

<script src="<?= esc(base_url('assets/dist/js/tutorial.js'), 'attr') ?>"></script>
<script src="<?= esc(base_url('assets/dist/js/portal.js'), 'attr') ?>"></script>
</body>
</html>
