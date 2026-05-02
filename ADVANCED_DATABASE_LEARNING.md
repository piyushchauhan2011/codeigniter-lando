# Advanced Database Learning Lab

This guide pairs with the in-app lab at:

```text
/learning/modules/database-lab
```

Use it after the job portal schema and demo data are available:

```bash
lando php spark migrate --all
lando php spark db:seed JobPortalDemoSeeder
```

The lab is app-only. It teaches production database ideas through the current CodeIgniter job portal schema without adding extra Lando services.

## Deep Indexing

Start with the public job listing query in `App\Models\JobModel::applyPublishedFilters()`. The lab prints the generated SQL, current `portal_jobs` indexes, and an `EXPLAIN` plan.

Things to practice:

- Compare the plan before and after adding a composite index.
- Put equality columns such as `status`, `employment_type`, and `category_id` before sort columns.
- Notice that `SELECT portal_jobs.*` prevents a small covering index from satisfying the whole query.
- Treat text search separately from normal B-tree indexing. `%term%` patterns usually need full-text, trigram, or external search.

Useful local experiments:

```sql
ALTER TABLE portal_jobs ADD INDEX idx_portal_jobs_public_sort (status, is_featured, created_at);
ALTER TABLE portal_jobs ADD INDEX idx_portal_jobs_type_sort (status, employment_type, is_featured, created_at);
ALTER TABLE portal_jobs ADD INDEX idx_portal_jobs_category_sort (status, category_id, is_featured, created_at);
```

For PostgreSQL, prefer explicit sort direction when it matches the query:

```sql
CREATE INDEX idx_portal_jobs_public_sort ON portal_jobs (status, is_featured DESC, created_at DESC);
```

## Transactions And Isolation Levels

The job portal already has two useful teaching paths:

- `App\Controllers\Seeker::apply()` wraps the application insert in a transaction.
- `App\Jobs\ProcessFeaturedJobPayment` updates payment attempts, payment intents, and featured job flags across several statements.

Print the transaction exercises:

```bash
lando php spark db-lab:transaction-script
```

Key lessons:

- A transaction protects a group of statements, but constraints still do important work. The unique key on `job_applications (job_id, seeker_user_id)` prevents duplicate applications.
- MySQL InnoDB defaults to `REPEATABLE READ`; PostgreSQL defaults to `READ COMMITTED`.
- Stronger isolation can reduce anomalies, but it can also increase waiting, serialization failures, or deadlocks.

## Locks And Deadlocks

Print the row-lock exercise:

```bash
lando php spark db-lab:lock-demo
```

Print a two-terminal deadlock script:

```bash
lando php spark db-lab:deadlock-script
```

The deadlock exercise intentionally locks `portal_jobs` and `job_applications` in opposite orders. The fix is to make every code path acquire locks in the same order and keep transactions short.

Practical rules:

- Lock rows in a consistent order.
- Avoid remote HTTP calls, queue waits, or file uploads while a transaction is open.
- Retry idempotent work when the database reports a deadlock.

## Replication

The lab classifies app reads instead of creating a local replica:

- Public job browsing can often read from a replica.
- Immediately after submitting an application, read from the primary because the user expects their new state.
- Payment status pages should use primary or sticky reads because queue updates are asynchronous.
- Admin analytics can often use a replica or warehouse.

The important mental model is read-after-write consistency. Replica lag is acceptable for some screens and confusing or harmful for others.

## Advanced Sharding

Resolve a simulated shard:

```bash
lando php spark db-lab:shard-route --employer=123 --job=456
lando php spark db-lab:shard-route --location=Remote
```

Compare shard keys:

- `employer_user_id` works well for employer dashboards and tenant-level data.
- `job_id` is stable for job-owned records but does not naturally group all employer data.
- Region/location sharding can help local browsing but makes global search and remote jobs harder.

Sharding usually moves complexity from storage to application routing, reporting, cross-shard queries, and migrations. Do not shard before simpler options such as indexing, caching, read replicas, and table partitioning are exhausted.

## MySQL Vs PostgreSQL

Important differences for this app:

- MySQL often stores booleans as `TINYINT(1)`; PostgreSQL has a native `boolean`.
- MySQL uses `AUTO_INCREMENT`; PostgreSQL uses identity columns or sequences.
- MySQL `LIKE` behavior depends on collation; PostgreSQL `LIKE` is case-sensitive and `ILIKE` is case-insensitive.
- MySQL full-text indexes and PostgreSQL `tsvector`/GIN indexes are not portable drop-in equivalents.
- Default isolation levels differ, so concurrency behavior can change during a database migration.

Use CodeIgniter migrations and query builder where they express the same concept cleanly, but keep engine-specific performance features isolated and documented.
