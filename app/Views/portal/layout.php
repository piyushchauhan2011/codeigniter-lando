<!doctype html>
<?php
$locale   = service('request')->getLocale();
$appTz    = config(\Config\App::class)->appTimezone;
$pl       = \Config\Services::portalLocale();
$isEn     = in_array($pl->getUrlPrefix(), ['', 'en'], true);
$isFr     = $pl->getUrlPrefix() === 'fr';
$urlEn    = $pl->siteUrlForLocale('en');
$urlFr    = $pl->siteUrlForLocale('fr');
$auth     = \Config\Services::portalAuth();
?>
<html lang="<?= esc($locale, 'attr') ?>" data-locale="<?= esc($locale, 'attr') ?>" data-app-timezone="<?= esc($appTz, 'attr') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? lang('Portal.nav_brand')) ?> · <?= esc(lang('Portal.site_title_suffix')) ?></title>
    <link rel="stylesheet" href="<?= esc(base_url('assets/dist/css/tutorialStyle.css'), 'attr') ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/dist/css/portal.css'), 'attr') ?>">
</head>
<body class="portal-page">
<header class="portal-page__header">
    <div class="portal-page__header-inner">
        <strong><a href="<?= portal_url('jobs') ?>" class="portal-page__brand"><?= esc(lang('Portal.nav_brand')) ?></a></strong>
        <nav class="portal-page__nav" aria-label="Main">
            <a class="portal-page__nav-link" href="<?= portal_url('jobs') ?>"><?= esc(lang('Portal.nav_browse')) ?></a>
            <a class="portal-page__nav-link" href="<?= portal_url('contact') ?>"><?= esc(lang('Portal.nav_contact')) ?></a>
            <a class="portal-page__nav-link" href="<?= site_url($pl->localizePath('learning/modules/featured-jobs')) ?>"><?= esc(lang('FeaturedJobs.nav_learning')) ?></a>
            <a class="portal-page__nav-link" href="<?= site_url($pl->localizePath('learning/modules/performance-lab')) ?>"><?= esc(lang('PerformanceLab.nav_learning')) ?></a>
            <a class="portal-page__nav-link" href="<?= site_url($pl->localizePath('learning/modules/database-lab')) ?>"><?= esc(lang('DatabaseLab.nav_learning')) ?></a>
            <?php if (feature_enabled('elkLabNav')): ?>
                <a class="portal-page__nav-link" href="<?= site_url('learning/elk') ?>">ELK Lab</a>
            <?php endif; ?>
            <?php if ($auth->check()): ?>
                <a class="portal-page__nav-link" href="<?= portal_url('dashboard') ?>"><?= esc(lang('Portal.nav_dashboard')) ?></a>
                <?php if ($auth->isAdmin()): ?>
                    <a class="portal-page__nav-link" href="<?= portal_url('admin') ?>">Admin</a>
                <?php endif; ?>
                <?php if ($auth->isEmployer()): ?>
                    <a class="portal-page__nav-link" href="<?= portal_url('employer') ?>"><?= esc(lang('Portal.nav_employer')) ?></a>
                <?php endif; ?>
                <?php if ($auth->isSeeker()): ?>
                    <a class="portal-page__nav-link" href="<?= portal_url('seeker') ?>"><?= esc(lang('Portal.nav_seeker')) ?></a>
                <?php endif; ?>
                <form action="<?= portal_url('logout') ?>" method="post" class="portal-page__logout">
                    <?= csrf_field() ?>
                    <button type="submit" class="portal-page__logout-button"><?= esc(lang('Portal.nav_sign_out')) ?></button>
                </form>
            <?php else: ?>
                <a class="portal-page__nav-link" href="<?= portal_url('login') ?>"><?= esc(lang('Portal.nav_sign_in')) ?></a>
                <a class="portal-page__nav-link" href="<?= portal_url('register') ?>"><?= esc(lang('Portal.nav_register')) ?></a>
            <?php endif; ?>
            <a href="<?= site_url('/') ?>" class="portal-page__nav-link portal-link--muted"><?= esc(lang('Portal.nav_home')) ?></a>
            <div class="locale-switcher" role="group" aria-label="<?= esc(lang('Portal.locale_en') . ' / ' . lang('Portal.locale_fr'), 'attr') ?>">
                <a href="<?= esc($urlEn, 'attr') ?>" class="locale-switcher__btn<?= $isEn ? ' locale-switcher__btn--active' : '' ?>" hreflang="en" lang="en"><?= esc(lang('Portal.locale_en')) ?></a>
                <a href="<?= esc($urlFr, 'attr') ?>" class="locale-switcher__btn<?= $isFr ? ' locale-switcher__btn--active' : '' ?>" hreflang="fr" lang="fr"><?= esc(lang('Portal.locale_fr')) ?></a>
            </div>
        </nav>
    </div>
</header>

<main class="portal-page__main">
    <?php if (session()->getFlashdata('message')): ?>
        <p class="portal-flash portal-flash--success"><?= esc(session()->getFlashdata('message')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <p class="portal-flash portal-flash--error"><?= esc(session()->getFlashdata('error')) ?></p>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>

    <p class="portal-local-time portal-text-muted" id="portal-local-time" hidden>
        <span class="portal-local-time__label"><?= esc(lang('Portal.local_time_label')) ?>:</span>
        <span class="portal-local-time__value" data-portal-local-clock></span>
    </p>
</main>

<?php
$featureFlagsForJs = service('featureFlags')->all();
?>
<script><?= 'window.__FEATURE_FLAGS__=' . json_encode($featureFlagsForJs, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';' ?></script>
<script src="<?= esc(base_url('assets/dist/js/tutorial.js'), 'attr') ?>"></script>
<script src="<?= esc(base_url('assets/dist/js/portal.js'), 'attr') ?>"></script>
</body>
</html>
