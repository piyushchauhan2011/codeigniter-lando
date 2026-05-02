<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card">
    <h2><?= esc($heading) ?></h2>
    <p><?= esc($description) ?></p>
    <p><strong><?= esc(lang('PerformanceLab.generated_at')) ?>:</strong> <code><?= esc($generatedAt) ?></code></p>
    <p class="portal-text-muted"><?= esc(lang('PerformanceLab.refresh_hint')) ?></p>
    <p><a class="portal-button portal-button--secondary" href="<?= esc($backUrl, 'attr') ?>"><?= esc(lang('PerformanceLab.back_to_lab')) ?></a></p>
</section>
<?php $this->endSection(); ?>
