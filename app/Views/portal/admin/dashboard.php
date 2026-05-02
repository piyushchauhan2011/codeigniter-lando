<?php $this->extend('portal/layout'); ?>

<?php $this->section('content'); ?>
<section class="portal-card">
    <h2>Admin dashboard</h2>
    <p class="portal-text-muted">
        This Shield-protected area is available only to users with the `admin` group and `admin.access` permission.
    </p>

    <div class="portal-stats">
        <p><strong><?= esc((string) $totalUsers) ?></strong><br>Total users</p>
        <p><strong><?= esc((string) $publishedJobs) ?></strong><br>Published jobs</p>
        <p><strong><?= esc((string) $totalApplications) ?></strong><br>Applications</p>
    </div>
</section>

<section class="portal-card">
    <h3>Shield users</h3>
    <div class="portal-table-wrap">
        <table class="portal-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Groups</th>
                    <th>Active</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= esc((string) $user->id) ?></td>
                        <td><?= esc((string) ($user->email ?? '')) ?></td>
                        <td><?= esc(implode(', ', $user->getGroups() ?? [])) ?></td>
                        <td><?= $user->isActivated() ? 'Yes' : 'No' ?></td>
                        <td><?= esc((string) $user->created_at) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php $this->endSection(); ?>
