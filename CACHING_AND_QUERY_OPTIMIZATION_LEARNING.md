# CodeIgniter Caching And Query Optimization

This guide uses the job portal app to explain practical performance topics:

- Page caching vs fragment/object caching
- Redis and Memcached as cache backends
- Database indexes and `EXPLAIN`
- Lazy loading, eager loading, and the N+1 query problem

## Local Cache Services

The Lando stack includes cache services for learning:

- `redis` on port `6379`
- `memcached` on port `11211`

After changing `.lando.yml`, rebuild once:

```bash
lando rebuild -y
lando start
```

Check the backing services directly:

```bash
lando ssh -s redis -c "redis-cli ping"
lando ssh -s memcached -c "printf 'version\r\n' | nc 127.0.0.1 11211"
```

## Switching CodeIgniter Cache Handlers

The default handler remains file cache, which stores values under `writable/cache`:

```ini
cache.handler = file
cache.backupHandler = dummy
cache.prefix = codeigniter_tutorial_
cache.ttl = 60
```

For Redis in this local project, use the Predis handler:

```ini
cache.handler = predis
cache.redis.host = redis
cache.redis.port = 6379
cache.redis.password =
cache.redis.database = 0
```

`predis` works well for local learning because `predis/predis` is already in the Composer dev dependencies. If you switch to `cache.handler = redis`, the appserver must also have the PHP `redis` extension.

For Memcached:

```ini
cache.handler = memcached
cache.memcached.host = memcached
cache.memcached.port = 11211
```

The Memcached handler requires the PHP `memcached` extension in the appserver. The service alone is not enough; CodeIgniter still needs a PHP client extension to talk to it.

After choosing a handler, smoke test CodeIgniter's cache layer:

```bash
lando php spark cache:smoke
```

The command writes one temporary value with a 60-second TTL, reads it back, and deletes it.

## Page Caching

Page caching stores the whole HTTP response for a URL. It is fastest because CodeIgniter can return cached HTML without running controller and model logic again.

Good candidates:

- Public marketing pages
- Public API responses that do not vary by user
- Public job listing pages only if the query string and localization rules are handled carefully

Risky candidates:

- Logged-in dashboards
- Pages with flash messages
- Pages with CSRF tokens
- Pages that show private state like "applied" or "saved"

The job detail page is a good example of why full-page caching can be dangerous. `Jobs::show()` loads a public job, but then checks the logged-in seeker state:

```php
if ($auth->check() && $auth->isSeeker()) {
    $applied  = model(JobApplicationModel::class)->hasApplied($jobId, (int) $auth->id());
    $saved    = model(SavedJobModel::class)->isSaved((int) $auth->id(), $jobId);
    $canApply = ! $applied && $job['status'] === 'published';
}
```

If the entire response were cached for one seeker, another seeker might see the wrong `applied`, `saved`, or `canApply` state.

## Fragment And Object Caching

Fragment caching stores part of a page. Object caching stores data used to render a page. In CodeIgniter, both are commonly implemented through `cache()`.

This app already has an object-cache example in `JobCategoryModel`:

```php
return cache()->remember(
    'portal_job_categories_v1',
    3600,
    fn (): array => $this->orderBy('name', 'ASC')->findAll(),
);
```

That cache entry is safe because job categories are shared by all users and rarely change. The rest of the page can still be dynamic.

Good object-cache targets in this app:

- Job categories for filters and forms
- Public job detail rows for anonymous users
- Public API job lists with a short TTL
- Small dashboard summaries such as counts

Poor cache targets:

- User-specific application state unless the user ID is part of the key
- Flash messages
- One-time forms with CSRF tokens
- Data that changes on every request

Use short, stable keys and always set TTLs. CodeIgniter's default cache key rules are stricter than raw Redis key rules, so this project uses underscore-style keys like `portal_job_categories_v1`.

## Redis Vs Memcached

Redis is a richer tool:

- Simple cache values
- Sessions
- Queues
- Locks
- Rate limits
- Counters
- Sets and sorted sets
- Optional persistence

Memcached is intentionally smaller:

- Fast key-value cache
- TTL-based expiry
- No rich data structures
- No persistence expectation

For this job portal, Redis is the better first learning backend because it can later support sessions, queue experiments, counters, and rate limits. Memcached is useful for learning the simpler "cache only" model.

## Cache Invalidation

Caching is easy to add and easy to make stale. Prefer these rules:

- Use TTLs on every cache write.
- Include version numbers in keys when the shape changes, for example `portal_job_categories_v1`.
- Delete or refresh keys when admin/editor workflows change the underlying data.
- Keep user-specific data out of shared keys.

For example, if a category admin feature is added later, it should delete `portal_job_categories_v1` after create, update, or delete.

## Public Job Listing Query

The public listing is built in `JobModel::applyPublishedFilters()`:

```php
$this->select('portal_jobs.*, employer_profiles.company_name')
    ->join('employer_profiles', 'employer_profiles.user_id = portal_jobs.employer_user_id', 'left')
    ->where('portal_jobs.status', 'published');
```

It can also filter by:

- `q`, using `LIKE` on `title` and `description`
- `location`, using `LIKE`
- `employment_type`, using equality
- `category_id`, using equality

Then it sorts:

```php
$this->orderBy('portal_jobs.is_featured', 'DESC')
    ->orderBy('portal_jobs.created_at', 'DESC');
```

The migration currently adds single-column indexes for common filters such as `status`, `location`, `employment_type`, and `category_id`. That is a good starting point, but a real listing query may benefit from a composite index that matches the most common filter and sort pattern.

## EXPLAIN

Use `EXPLAIN` to see how MySQL plans to run a query before and after index changes.

Basic public listing:

```sql
EXPLAIN
SELECT portal_jobs.*, employer_profiles.company_name
FROM portal_jobs
LEFT JOIN employer_profiles
  ON employer_profiles.user_id = portal_jobs.employer_user_id
WHERE portal_jobs.status = 'published'
ORDER BY portal_jobs.is_featured DESC, portal_jobs.created_at DESC
LIMIT 10;
```

Listing filtered by employment type:

```sql
EXPLAIN
SELECT portal_jobs.*, employer_profiles.company_name
FROM portal_jobs
LEFT JOIN employer_profiles
  ON employer_profiles.user_id = portal_jobs.employer_user_id
WHERE portal_jobs.status = 'published'
  AND portal_jobs.employment_type = 'full-time'
ORDER BY portal_jobs.is_featured DESC, portal_jobs.created_at DESC
LIMIT 10;
```

Listing filtered by category:

```sql
EXPLAIN
SELECT portal_jobs.*, employer_profiles.company_name
FROM portal_jobs
LEFT JOIN employer_profiles
  ON employer_profiles.user_id = portal_jobs.employer_user_id
WHERE portal_jobs.status = 'published'
  AND portal_jobs.category_id = 1
ORDER BY portal_jobs.is_featured DESC, portal_jobs.created_at DESC
LIMIT 10;
```

In the output, focus on:

- `key`: which index MySQL chose
- `rows`: how many rows MySQL expects to inspect
- `type`: `ref` or `range` is usually better than `ALL`
- `Extra`: `Using filesort` can mean MySQL could not satisfy ordering from the index

Example composite indexes to test in a learning database:

```sql
ALTER TABLE portal_jobs
  ADD INDEX idx_portal_jobs_public_sort (status, is_featured, created_at);

ALTER TABLE portal_jobs
  ADD INDEX idx_portal_jobs_public_type_sort (status, employment_type, is_featured, created_at);

ALTER TABLE portal_jobs
  ADD INDEX idx_portal_jobs_public_category_sort (status, category_id, is_featured, created_at);
```

Do not add every possible index blindly. Each index speeds some reads but slows writes and uses memory/disk. Add the indexes that match real, frequent queries.

## Lazy Loading Vs Eager Loading

Lazy loading means fetching related data only when a later line of code asks for it. It is simple for one record, but it can be expensive for lists.

Eager loading means fetching related data up front, usually with a join or one batch query.

This app uses eager loading in `JobApplicationModel::findAllForSeeker()`:

```php
return model(static::class, false)
    ->select('job_applications.*, portal_jobs.title AS job_title, portal_jobs.location')
    ->join('portal_jobs', 'portal_jobs.id = job_applications.job_id')
    ->where('job_applications.seeker_user_id', $seekerUserId)
    ->orderBy('job_applications.created_at', 'DESC')
    ->findAll();
```

That returns applications and the related job title/location in one query.

## N+1 Query Problem

N+1 means:

1. Run one query to get a list.
2. Run one more query for each row in the list.

Bad pattern:

```php
$applications = model(JobApplicationModel::class)->where('seeker_user_id', $userId)->findAll();

foreach ($applications as $application) {
    $job = model(JobModel::class)->find((int) $application['job_id']);
}
```

If the seeker has 25 applications, this runs 26 queries.

Better pattern:

```php
$applications = model(JobApplicationModel::class)->findAllForSeeker($userId);
```

That uses one joined query.

Another possible N+1 in a job portal is listing jobs and then loading each employer profile inside the loop. This app avoids that in `JobModel::applyPublishedFilters()` by joining `employer_profiles` before rendering the public listing.

## Suggested Experiments

1. Start with `cache.handler = file` and run `lando php spark cache:smoke`.
2. Switch to `cache.handler = predis`, rebuild/start Lando if needed, and run `lando php spark cache:smoke` again.
3. Open `/jobs` and `/jobs/{id}` while watching the Debug Toolbar database query count.
4. Run the `EXPLAIN` examples in Adminer against the Lando database.
5. Add one composite index in a local throwaway database, rerun `EXPLAIN`, and compare `key`, `rows`, and `Extra`.
6. Compare the eager-loaded seeker application query with the bad N+1 pattern to see how query counts grow.
