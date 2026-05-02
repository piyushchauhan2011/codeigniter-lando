<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card">
    <h2><?= esc($title) ?></h2>
    <p class="portal-text-muted"><?= esc(lang('DatabaseLab.page_intro')) ?></p>

    <section class="portal-card portal-card--nested" aria-labelledby="database-indexing">
        <h3 id="database-indexing">Indexing deeply</h3>
        <p>This is the same public job listing query used by the app. Compare the SQL, current indexes, and EXPLAIN output before adding any suggested index locally.</p>
        <p><strong>Driver:</strong> <code><?= esc($indexing['plan']['driver']) ?></code></p>
        <pre><code><?= esc($indexing['plan']['sql']) ?></code></pre>

        <?php if ($indexing['plan']['error'] !== null): ?>
            <p class="portal-flash portal-flash--error"><?= esc($indexing['plan']['error']) ?></p>
        <?php elseif ($indexing['plan']['rows'] === []): ?>
            <p>No EXPLAIN rows were returned.</p>
        <?php else: ?>
            <div class="portal-table-wrap">
                <table class="portal-table">
                    <thead>
                    <tr>
                        <?php foreach ($indexing['plan']['columns'] as $column): ?>
                            <th><?= esc($column) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($indexing['plan']['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($indexing['plan']['columns'] as $column): ?>
                                <td><?= esc((string) ($row[$column] ?? '')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h4>Current portal_jobs indexes</h4>
        <?php if ($indexing['indexes'] === []): ?>
            <p>No index metadata was available for this driver.</p>
        <?php else: ?>
            <div class="portal-table-wrap">
                <table class="portal-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Fields</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($indexing['indexes'] as $index): ?>
                        <tr>
                            <td><code><?= esc($index['name']) ?></code></td>
                            <td><?= esc($index['type']) ?></td>
                            <td><code><?= esc(implode(', ', $index['fields'])) ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h4>Index experiments</h4>
        <?php foreach ($indexing['suggestedIndexes'] as $suggestion): ?>
            <article class="portal-card portal-card--nested">
                <h5><?= esc($suggestion['title']) ?></h5>
                <p><?= esc($suggestion['why']) ?></p>
                <p><strong>MySQL:</strong></p>
                <pre><code><?= esc($suggestion['mysql']) ?></code></pre>
                <p><strong>PostgreSQL:</strong></p>
                <pre><code><?= esc($suggestion['postgres']) ?></code></pre>
            </article>
        <?php endforeach; ?>

        <h4>Rules to internalize</h4>
        <ul class="portal-list">
            <?php foreach ($indexing['notes'] as $note): ?>
                <li><strong><?= esc($note['heading']) ?>:</strong> <?= esc($note['body']) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="portal-card portal-card--nested" aria-labelledby="database-transactions">
        <h3 id="database-transactions">Transactions and isolation levels</h3>
        <p>The application submit path and featured-payment queue path are real examples where atomicity, uniqueness, and retryability matter.</p>
        <ul class="portal-list">
            <li><strong><?= esc($transactions['applicationFlow']['title']) ?>:</strong> <?= esc($transactions['applicationFlow']['point']) ?> <code><?= esc($transactions['applicationFlow']['path']) ?></code></li>
            <li><strong><?= esc($transactions['paymentFlow']['title']) ?>:</strong> <?= esc($transactions['paymentFlow']['point']) ?> <code><?= esc($transactions['paymentFlow']['path']) ?></code></li>
        </ul>

        <h4>Isolation SQL</h4>
        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                <tr>
                    <th>Level</th>
                    <th>MySQL</th>
                    <th>PostgreSQL</th>
                    <th>Use case</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions['isolationLevels'] as $level): ?>
                    <tr>
                        <td><?= esc($level['name']) ?></td>
                        <td><code><?= esc($level['mysql']) ?></code></td>
                        <td><code><?= esc($level['postgres']) ?></code></td>
                        <td><?= esc($level['useCase']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h4>Exercise script</h4>
        <pre><code><?= esc(implode("\n", $transactions['script'])) ?></code></pre>
    </section>

    <section class="portal-card portal-card--nested" aria-labelledby="database-locks">
        <h3 id="database-locks">Locks and deadlocks</h3>
        <p><strong>Driver:</strong> <code><?= esc($locks['driver']) ?></code>. <?= esc($locks['message']) ?></p>
        <h4>Row lock sequence</h4>
        <pre><code><?= esc(implode("\n", $locks['lockSql'])) ?></code></pre>
        <h4>Deadlock sequence</h4>
        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                <tr>
                    <th>Terminal A</th>
                    <th>Terminal B</th>
                </tr>
                </thead>
                <tbody>
                <?php $steps = max(count($locks['deadlockSql']['terminalA']), count($locks['deadlockSql']['terminalB'])); ?>
                <?php for ($i = 0; $i < $steps; $i++): ?>
                    <tr>
                        <td><code><?= esc($locks['deadlockSql']['terminalA'][$i] ?? '') ?></code></td>
                        <td><code><?= esc($locks['deadlockSql']['terminalB'][$i] ?? '') ?></code></td>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
        <ul class="portal-list">
            <?php foreach ($locks['rules'] as $rule): ?>
                <li><?= esc($rule) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="portal-card portal-card--nested" aria-labelledby="database-replication">
        <h3 id="database-replication">Replication</h3>
        <p>This app-only lab classifies reads as replica-safe, primary-only, or stale-read-risk without adding a replica service.</p>
        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                <tr>
                    <th>Flow</th>
                    <th>Classification</th>
                    <th>Reason</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($replication as $example): ?>
                    <tr>
                        <td><?= esc($example['name']) ?></td>
                        <td><?= esc($example['classification']) ?></td>
                        <td><?= esc($example['reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="portal-card portal-card--nested" aria-labelledby="database-sharding">
        <h3 id="database-sharding">Advanced sharding</h3>
        <p>Current simulated route: <strong><?= esc($shardRoute['shard']) ?></strong> using <?= esc($shardRoute['strategy']) ?>.</p>
        <pre><code><?= esc(json_encode($shardRoute['input'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) ?></code></pre>
        <ul class="portal-list">
            <?php foreach ($shardRoute['caveats'] as $caveat): ?>
                <li><?= esc($caveat) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="portal-card portal-card--nested" aria-labelledby="database-dialects">
        <h3 id="database-dialects">MySQL vs PostgreSQL differences</h3>
        <div class="portal-table-wrap">
            <table class="portal-table">
                <thead>
                <tr>
                    <th>Topic</th>
                    <th>MySQL</th>
                    <th>PostgreSQL</th>
                    <th>Job portal impact</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($dialectComparisons as $comparison): ?>
                    <tr>
                        <td><?= esc($comparison['topic']) ?></td>
                        <td><?= esc($comparison['mysql']) ?></td>
                        <td><?= esc($comparison['postgres']) ?></td>
                        <td><?= esc($comparison['appImpact']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>
<?php $this->endSection(); ?>
