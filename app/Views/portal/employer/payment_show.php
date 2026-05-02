<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card">
    <h2><?= esc($title) ?></h2>
    <p><a href="<?= portal_url('employer') ?>">&larr; Back to employer dashboard</a></p>

    <section class="portal-card portal-card--nested">
        <h3>Payment intent</h3>
        <dl>
            <dt>Job</dt>
            <dd><?= esc($intent['job_title']) ?></dd>
            <dt>Status</dt>
            <dd><strong><?= esc($intent['status']) ?></strong></dd>
            <dt>Amount</dt>
            <dd><?= esc($intent['currency']) ?> <?= esc(number_format(((int) $intent['amount_cents']) / 100, 2)) ?></dd>
            <dt>Fake gateway scenario</dt>
            <dd><?= esc($intent['scenario']) ?></dd>
            <dt>Idempotency key</dt>
            <dd><code><?= esc($intent['idempotency_key']) ?></code></dd>
            <?php if (! empty($intent['provider_reference'])): ?>
                <dt>Provider reference</dt>
                <dd><code><?= esc($intent['provider_reference']) ?></code></dd>
            <?php endif; ?>
            <?php if (! empty($intent['last_error'])): ?>
                <dt>Last error</dt>
                <dd><?= esc($intent['last_error']) ?></dd>
            <?php endif; ?>
        </dl>
    </section>

    <section class="portal-card portal-card--nested">
        <h3>Worker attempts</h3>
        <?php if ($attempts === []): ?>
            <p class="portal-text-muted">No worker has processed this intent yet. Run <code>lando queue-work-payments</code>.</p>
        <?php else: ?>
            <table class="portal-table">
                <thead><tr><th>#</th><th>Status</th><th>Created</th><th>Provider reference</th><th>Error</th></tr></thead>
                <tbody>
                    <?php foreach ($attempts as $attempt): ?>
                        <tr>
                            <td><?= esc((string) $attempt['attempt_number']) ?></td>
                            <td><?= esc($attempt['status']) ?></td>
                            <td><?= esc(portal_localized_datetime((string) $attempt['created_at'])) ?></td>
                            <td><?= ! empty($attempt['provider_reference']) ? '<code>' . esc($attempt['provider_reference']) . '</code>' : '<span class="portal-text-muted">none</span>' ?></td>
                            <td><?= esc((string) ($attempt['error_message'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <?php if (is_array($deadLetter)): ?>
        <section class="portal-card portal-card--nested">
            <h3>Domain dead letter</h3>
            <p><strong><?= esc($deadLetter['reason']) ?></strong>: <?= esc((string) $deadLetter['error_message']) ?></p>
            <?php if (! empty($deadLetter['payload'])): ?>
                <pre><code><?= esc((string) $deadLetter['payload']) ?></code></pre>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</section>
<?php $this->endSection(); ?>
