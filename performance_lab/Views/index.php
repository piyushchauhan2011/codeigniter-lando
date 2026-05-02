<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card">
    <h2><?= esc($title) ?></h2>
    <p class="portal-text-muted"><?= esc(lang('PerformanceLab.page_intro')) ?></p>

    <section class="portal-card portal-card--nested" aria-labelledby="performance-cache-config">
        <h3 id="performance-cache-config"><?= esc(lang('PerformanceLab.cache_config_heading')) ?></h3>
        <ul class="portal-list">
            <li><strong><?= esc(lang('PerformanceLab.cache_handler')) ?>:</strong> <code><?= esc($cacheConfig['handler']) ?></code></li>
            <li><strong><?= esc(lang('PerformanceLab.cache_backup')) ?>:</strong> <code><?= esc($cacheConfig['backupHandler']) ?></code></li>
            <li><strong><?= esc(lang('PerformanceLab.cache_prefix')) ?>:</strong> <code><?= esc($cacheConfig['prefix'] === '' ? '(none)' : $cacheConfig['prefix']) ?></code></li>
            <li><strong><?= esc(lang('PerformanceLab.cache_default_ttl')) ?>:</strong> <?= esc((string) $cacheConfig['ttl']) ?>s</li>
        </ul>
    </section>

    <section class="portal-card portal-card--nested" aria-labelledby="performance-page-cache">
        <h3 id="performance-page-cache"><?= esc(lang('PerformanceLab.page_cache_heading')) ?></h3>
        <p><?= esc(lang('PerformanceLab.page_cache_intro')) ?></p>
        <p>
            <a class="portal-button" href="<?= esc($cachedPageUrl, 'attr') ?>"><?= esc(lang('PerformanceLab.open_cached_page')) ?></a>
            <a class="portal-button portal-button--secondary" href="<?= esc($uncachedPageUrl, 'attr') ?>"><?= esc(lang('PerformanceLab.open_uncached_page')) ?></a>
        </p>
        <p class="portal-text-muted"><?= esc($jobShowCacheWarning) ?></p>
    </section>

    <section class="portal-card portal-card--nested" aria-labelledby="performance-object-cache">
        <h3 id="performance-object-cache"><?= esc(lang('PerformanceLab.object_cache_heading')) ?></h3>
        <p><?= esc(lang('PerformanceLab.object_cache_intro')) ?></p>
        <form action="<?= esc($clearCacheAction, 'attr') ?>" method="post">
            <?= csrf_field() ?>
            <button type="submit" class="portal-button portal-button--secondary"><?= esc(lang('PerformanceLab.clear_demo_cache')) ?></button>
        </form>

        <h4><?= esc(lang('PerformanceLab.category_cache_heading')) ?></h4>
        <ul class="portal-list">
            <li><strong><?= esc(lang('PerformanceLab.cache_key')) ?>:</strong> <code><?= esc($categoryCache['key']) ?></code></li>
            <li><strong><?= esc(lang('PerformanceLab.cache_status')) ?>:</strong> <?= esc($categoryCache['hit'] ? lang('PerformanceLab.cache_hit') : lang('PerformanceLab.cache_miss')) ?></li>
            <li><strong><?= esc(lang('PerformanceLab.cache_ttl')) ?>:</strong> <?= esc((string) $categoryCache['ttl']) ?>s</li>
            <li><strong><?= esc(lang('PerformanceLab.cache_count')) ?>:</strong> <?= esc((string) $categoryCache['count']) ?></li>
            <li><strong><?= esc(lang('PerformanceLab.elapsed')) ?>:</strong> <?= esc((string) $categoryCache['elapsedMs']) ?>ms</li>
        </ul>

        <h4><?= esc(lang('PerformanceLab.job_cache_heading')) ?></h4>
        <ul class="portal-list">
            <li><strong><?= esc(lang('PerformanceLab.cache_key')) ?>:</strong> <code><?= esc($jobListCache['key']) ?></code></li>
            <li><strong><?= esc(lang('PerformanceLab.cache_status')) ?>:</strong> <?= esc($jobListCache['hit'] ? lang('PerformanceLab.cache_hit') : lang('PerformanceLab.cache_miss')) ?></li>
            <li><strong><?= esc(lang('PerformanceLab.cache_ttl')) ?>:</strong> <?= esc((string) $jobListCache['ttl']) ?>s</li>
            <li><strong><?= esc(lang('PerformanceLab.cache_count')) ?>:</strong> <?= esc((string) $jobListCache['count']) ?></li>
            <li><strong><?= esc(lang('PerformanceLab.elapsed')) ?>:</strong> <?= esc((string) $jobListCache['elapsedMs']) ?>ms</li>
        </ul>
    </section>

    <section class="portal-card portal-card--nested" aria-labelledby="performance-query-plan">
        <h3 id="performance-query-plan"><?= esc(lang('PerformanceLab.query_heading')) ?></h3>
        <p><?= esc(lang('PerformanceLab.query_intro')) ?></p>
        <p><strong><?= esc(lang('PerformanceLab.query_driver')) ?>:</strong> <code><?= esc($queryPlan['driver']) ?></code></p>
        <pre><code><?= esc($queryPlan['sql']) ?></code></pre>

        <?php if ($queryPlan['error'] !== null): ?>
            <p class="portal-flash portal-flash--error"><?= esc($queryPlan['error']) ?></p>
        <?php elseif ($queryPlan['rows'] === []): ?>
            <p><?= esc(lang('PerformanceLab.query_empty')) ?></p>
        <?php else: ?>
            <div class="portal-table-wrap">
                <table class="portal-table">
                    <thead>
                    <tr>
                        <?php foreach ($queryPlan['columns'] as $column): ?>
                            <th><?= esc($column) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($queryPlan['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($queryPlan['columns'] as $column): ?>
                                <td><?= esc((string) ($row[$column] ?? '')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h4><?= esc(lang('PerformanceLab.suggested_indexes_heading')) ?></h4>
        <ul class="portal-list">
            <?php foreach ($suggestedIndexes as $sql): ?>
                <li><code><?= esc($sql) ?></code></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="portal-card portal-card--nested" aria-labelledby="performance-loading">
        <h3 id="performance-loading"><?= esc(lang('PerformanceLab.loading_heading')) ?></h3>
        <p><?= esc(lang('PerformanceLab.loading_intro')) ?></p>
        <?php if ($loadingComparison['seekerUserId'] === null): ?>
            <p><?= esc(lang('PerformanceLab.loading_empty')) ?></p>
        <?php else: ?>
            <p><strong><?= esc(lang('PerformanceLab.loading_sample_user')) ?>:</strong> <?= esc((string) $loadingComparison['seekerUserId']) ?></p>
            <div class="portal-table-wrap">
                <table class="portal-table">
                    <thead>
                    <tr>
                        <th><?= esc(lang('PerformanceLab.loading_strategy')) ?></th>
                        <th><?= esc(lang('PerformanceLab.loading_queries')) ?></th>
                        <th><?= esc(lang('PerformanceLab.loading_rows')) ?></th>
                        <th><?= esc(lang('PerformanceLab.elapsed')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ([$loadingComparison['lazy'], $loadingComparison['eager']] as $result): ?>
                        <tr>
                            <td><?= esc($result['label']) ?></td>
                            <td><?= esc((string) $result['queries']) ?></td>
                            <td><?= esc((string) count($result['rows'])) ?></td>
                            <td><?= esc((string) $result['elapsedMs']) ?>ms</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</section>
<?php $this->endSection(); ?>
