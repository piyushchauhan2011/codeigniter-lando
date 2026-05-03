<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card elk-lab">
    <h2><?= esc($title) ?></h2>
    <p class="portal-text-muted">
        Generate local logs, traces, and PHP exceptions for Kibana Discover and Elastic APM. Browser JS errors go to <strong>Sentry</strong> — see <code>docs/SENTRY_SELF_HOSTED.md</code> in the repo.
    </p>

    <div class="portal-card portal-card--nested">
        <h3>PHP logs and errors</h3>
        <p>Use these actions to create searchable JSON logs and APM-style PHP exceptions.</p>
        <p class="portal-actions">
            <a class="portal-button" href="<?= site_url('learning/elk/log-demo') ?>">Write logs</a>
            <a class="portal-button portal-button--secondary" href="<?= site_url('learning/elk/handled-error') ?>">Handled exception</a>
            <a class="portal-button portal-button--secondary" href="<?= site_url('learning/elk/slow-request') ?>">Slow request</a>
            <a class="portal-button portal-button--secondary" href="<?= site_url('learning/elk/not-found') ?>">404 event</a>
        </p>
        <p class="portal-text-muted">
            Open the unhandled exception in a new tab when you want to produce a visible 500:
            <a href="<?= site_url('learning/elk/unhandled-error') ?>"><?= esc(site_url('learning/elk/unhandled-error')) ?></a>
        </p>
    </div>

    <div class="portal-card portal-card--nested">
        <h3>Browser JS error (Sentry)</h3>
        <p>
            Run self-hosted Sentry (see <code>docs/SENTRY_SELF_HOSTED.md</code>), set <code>VITE_SENTRY_DSN</code>, run <code>pnpm build</code> so source maps upload when configured, then click below.
            Open your Sentry project → <strong>Issues</strong>; stacks symbolicate against uploaded maps when release/env match.
        </p>
        <button type="button" class="portal-button" data-sentry-error-demo>Demo JS error (Sentry)</button>
    </div>

    <div class="portal-card portal-card--nested">
        <h3>Useful Kibana queries</h3>
        <ul>
            <li><code>event.dataset: codeigniter.request</code> for request logs.</li>
            <li><code>event.dataset: codeigniter.elk_lab</code> for generated demo logs.</li>
            <li><code>log.level: error or http.response.status_code &gt;= 500</code> for errors.</li>
            <li><code>labels.duration_ms &gt; 500</code> for slow local requests.</li>
        </ul>
    </div>
</section>
<?php $this->endSection(); ?>
