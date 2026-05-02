<?php

declare(strict_types=1);

namespace DatabaseLab\Models;

use App\Models\JobModel;
use CodeIgniter\Model;
use Throwable;

class DatabaseLabModel extends Model
{
    protected $table         = 'portal_jobs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [];

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function indexingAnalysis(array $filters = []): array
    {
        return [
            'plan'             => $this->publicJobListingExplain($filters),
            'indexes'          => $this->indexMetadata('portal_jobs'),
            'suggestedIndexes' => $this->suggestedIndexes(),
            'notes'            => $this->indexingNotes(),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function publicJobListingExplain(array $filters = []): array
    {
        $model = model(JobModel::class, false);
        $model->applyPublishedFilters($filters);

        $sql = $model->builder()->getCompiledSelect(false);
        $db  = db_connect();

        try {
            $prefix = $db->DBDriver === 'SQLite3' ? 'EXPLAIN QUERY PLAN ' : 'EXPLAIN ';
            $rows   = $db->query($prefix . $sql)->getResultArray();

            return [
                'sql'     => $sql,
                'driver'  => $db->DBDriver,
                'columns' => $rows === [] ? [] : array_keys($rows[0]),
                'rows'    => $rows,
                'error'   => null,
            ];
        } catch (Throwable $exception) {
            return [
                'sql'     => $sql,
                'driver'  => $db->DBDriver,
                'columns' => [],
                'rows'    => [],
                'error'   => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return list<array{name: string, type: string, fields: list<string>}>
     */
    public function indexMetadata(string $table): array
    {
        $db = db_connect();

        try {
            $indexes = $db->getIndexData($table);
        } catch (Throwable) {
            return [];
        }

        $normalized = [];
        foreach ($indexes as $index) {
            $fields = $index->fields ?? [];
            if (! is_array($fields)) {
                $fields = [(string) $fields];
            }

            $normalized[] = [
                'name'   => (string) ($index->name ?? 'unknown'),
                'type'   => (string) ($index->type ?? 'INDEX'),
                'fields' => array_values(array_map(static fn ($field): string => (string) $field, $fields)),
            ];
        }

        usort($normalized, static fn (array $left, array $right): int => $left['name'] <=> $right['name']);

        return $normalized;
    }

    /**
     * @return list<array{title: string, why: string, mysql: string, postgres: string}>
     */
    public function suggestedIndexes(): array
    {
        return [
            [
                'title'    => 'Default public listing sort',
                'why'      => 'Matches the always-present status predicate and featured/newest ordering.',
                'mysql'    => 'ALTER TABLE portal_jobs ADD INDEX idx_portal_jobs_public_sort (status, is_featured, created_at);',
                'postgres' => 'CREATE INDEX idx_portal_jobs_public_sort ON portal_jobs (status, is_featured DESC, created_at DESC);',
            ],
            [
                'title'    => 'Employment type filtered listing',
                'why'      => 'Keeps equality columns before the sort columns for the common type filter.',
                'mysql'    => 'ALTER TABLE portal_jobs ADD INDEX idx_portal_jobs_type_sort (status, employment_type, is_featured, created_at);',
                'postgres' => 'CREATE INDEX idx_portal_jobs_type_sort ON portal_jobs (status, employment_type, is_featured DESC, created_at DESC);',
            ],
            [
                'title'    => 'Category filtered listing',
                'why'      => 'Helps category pages avoid scanning all published jobs before sorting.',
                'mysql'    => 'ALTER TABLE portal_jobs ADD INDEX idx_portal_jobs_category_sort (status, category_id, is_featured, created_at);',
                'postgres' => 'CREATE INDEX idx_portal_jobs_category_sort ON portal_jobs (status, category_id, is_featured DESC, created_at DESC);',
            ],
            [
                'title'    => 'Search experiment',
                'why'      => 'Leading-wildcard LIKE searches are not normal B-tree index friendly; use engine-specific search indexes.',
                'mysql'    => 'ALTER TABLE portal_jobs ADD FULLTEXT INDEX idx_portal_jobs_search (title, description);',
                'postgres' => "CREATE INDEX idx_portal_jobs_search ON portal_jobs USING GIN (to_tsvector('english', title || ' ' || description));",
            ],
        ];
    }

    /**
     * @return list<array{heading: string, body: string}>
     */
    public function indexingNotes(): array
    {
        return [
            [
                'heading' => 'Column order matters',
                'body'    => 'For this listing, equality predicates such as status, employment_type, and category_id usually belong before sort columns.',
            ],
            [
                'heading' => 'Covering indexes need narrow SELECT lists',
                'body'    => 'The current query selects portal_jobs.*, so an index can help find rows but cannot cover the whole result efficiently.',
            ],
            [
                'heading' => 'LIKE patterns are different',
                'body'    => "A prefix search such as 'Remote%' can use a B-tree index more often than '%Remote%', which usually requires full-text or trigram search.",
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transactionLab(): array
    {
        return [
            'driver'          => db_connect()->DBDriver,
            'applicationFlow' => [
                'title' => 'Submitting a job application',
                'path'  => 'app/Controllers/Seeker.php',
                'point' => 'A unique key on (job_id, seeker_user_id) protects against duplicate applications while the transaction keeps the insert atomic.',
            ],
            'paymentFlow' => [
                'title' => 'Processing a featured job payment',
                'path'  => 'app/Jobs/ProcessFeaturedJobPayment.php',
                'point' => 'Attempts, payment intents, and job featured flags should move together or be recoverable through idempotency.',
            ],
            'isolationLevels' => $this->isolationLevels(),
            'script'          => $this->transactionExerciseScript(),
        ];
    }

    /**
     * @return list<array{name: string, mysql: string, postgres: string, useCase: string}>
     */
    public function isolationLevels(): array
    {
        return [
            [
                'name'     => 'READ COMMITTED',
                'mysql'    => 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED;',
                'postgres' => 'BEGIN ISOLATION LEVEL READ COMMITTED;',
                'useCase'  => 'Good default for many web requests when you want each statement to see committed data.',
            ],
            [
                'name'     => 'REPEATABLE READ',
                'mysql'    => 'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;',
                'postgres' => 'BEGIN ISOLATION LEVEL REPEATABLE READ;',
                'useCase'  => 'Keeps reads stable inside one transaction; MySQL uses this as its InnoDB default.',
            ],
            [
                'name'     => 'SERIALIZABLE',
                'mysql'    => 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;',
                'postgres' => 'BEGIN ISOLATION LEVEL SERIALIZABLE;',
                'useCase'  => 'Strongest isolation, useful for teaching anomalies but often too restrictive for hot web paths.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function transactionExerciseScript(): array
    {
        return [
            'BEGIN;',
            "SELECT id, status FROM portal_jobs WHERE status = 'published' ORDER BY id LIMIT 1;",
            "INSERT INTO job_applications (job_id, seeker_user_id, cover_letter, resume_path, status, created_at, updated_at) VALUES (...);",
            'COMMIT;',
            'Repeat with ROLLBACK to prove the application row disappears.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function lockLab(): array
    {
        $driver    = db_connect()->DBDriver;
        $supported = in_array($driver, ['MySQLi', 'Postgre'], true);

        return [
            'driver'      => $driver,
            'supported'   => $supported,
            'message'     => $supported
                ? 'This driver supports row-level lock exercises with SELECT ... FOR UPDATE.'
                : 'This driver is used for tests or lightweight development, so the lab prints the SQL instead of taking real row locks.',
            'lockSql'     => $this->lockSql(),
            'deadlockSql' => $this->deadlockSql(),
            'rules'       => [
                'Lock rows in a consistent order across code paths.',
                'Keep transactions short; do not wait on HTTP calls or queues while holding locks.',
                'Have retry logic for detected deadlocks on idempotent operations.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function lockSql(): array
    {
        return [
            'BEGIN;',
            'SELECT id, status FROM portal_jobs WHERE id = :job_id: FOR UPDATE;',
            "UPDATE portal_jobs SET status = 'draft' WHERE id = :job_id:;",
            'COMMIT;',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public function deadlockSql(): array
    {
        return [
            'terminalA' => [
                'BEGIN;',
                'SELECT id FROM portal_jobs WHERE id = 1 FOR UPDATE;',
                'SELECT id FROM job_applications WHERE id = 1 FOR UPDATE;',
                'COMMIT;',
            ],
            'terminalB' => [
                'BEGIN;',
                'SELECT id FROM job_applications WHERE id = 1 FOR UPDATE;',
                'SELECT id FROM portal_jobs WHERE id = 1 FOR UPDATE;',
                'COMMIT;',
            ],
        ];
    }

    /**
     * @return list<array{name: string, classification: string, reason: string}>
     */
    public function replicationExamples(): array
    {
        return [
            [
                'name'           => 'Public job browse',
                'classification' => 'read-replica candidate',
                'reason'         => 'Anonymous browsing tolerates slight replica lag if featured status is not business-critical to the viewer.',
            ],
            [
                'name'           => 'After submitting an application',
                'classification' => 'primary read required',
                'reason'         => 'Read-after-write consistency matters because the seeker expects to immediately see the new application state.',
            ],
            [
                'name'           => 'Payment status page',
                'classification' => 'primary or sticky read',
                'reason'         => 'Queue workers update payment rows asynchronously; stale reads make the UI confusing.',
            ],
            [
                'name'           => 'Admin analytics',
                'classification' => 'replica or warehouse candidate',
                'reason'         => 'Aggregates are expensive and usually tolerate lag better than transactional screens.',
            ],
        ];
    }

    /**
     * @return array{shard: string, strategy: string, input: array<string, int|string>, caveats: list<string>}
     */
    public function shardRoute(int $employerUserId = 0, int $jobId = 0, string $location = ''): array
    {
        $key      = 'global';
        $strategy = 'fallback';

        if ($employerUserId > 0) {
            $key      = 'employer:' . $employerUserId;
            $strategy = 'tenant/employer shard key';
        } elseif ($jobId > 0) {
            $key      = 'job:' . $jobId;
            $strategy = 'job-id shard key';
        } elseif ($location !== '') {
            $key      = 'location:' . strtolower(trim($location));
            $strategy = 'geographic shard key';
        }

        $shardNumber = (crc32($key) % 4) + 1;

        return [
            'shard'    => 'job_portal_shard_' . $shardNumber,
            'strategy' => $strategy,
            'input'    => [
                'employer_user_id' => $employerUserId,
                'job_id'           => $jobId,
                'location'         => $location,
            ],
            'caveats' => [
                'Employer dashboards are easy with tenant sharding because one employer maps to one shard.',
                'Public search across all jobs becomes scatter-gather unless you maintain a separate search index.',
                'Applications connect seekers and jobs, so the owning shard must be chosen deliberately.',
            ],
        ];
    }

    /**
     * @return list<array{topic: string, mysql: string, postgres: string, appImpact: string}>
     */
    public function mysqlPostgresDifferences(): array
    {
        return [
            [
                'topic'     => 'Booleans',
                'mysql'     => 'Commonly stored as TINYINT(1), as this app does for portal_jobs.is_featured.',
                'postgres'  => 'Native boolean type with true/false literals.',
                'appImpact' => 'Migrations should avoid assuming TINYINT when portability is a goal.',
            ],
            [
                'topic'     => 'Auto-increment',
                'mysql'     => 'AUTO_INCREMENT on integer primary keys.',
                'postgres'  => 'Identity columns or sequences.',
                'appImpact' => 'CodeIgniter forge hides most of this when migrations use auto_increment.',
            ],
            [
                'topic'     => 'Case sensitivity and LIKE',
                'mysql'     => 'Depends heavily on collation; many default collations are case-insensitive.',
                'postgres'  => 'LIKE is case-sensitive; ILIKE is case-insensitive.',
                'appImpact' => 'Job search behavior can change when moving drivers.',
            ],
            [
                'topic'     => 'Full-text search',
                'mysql'     => 'FULLTEXT indexes on suitable table engines/columns.',
                'postgres'  => 'tsvector/tsquery with GIN indexes, or trigram indexes for fuzzy matching.',
                'appImpact' => 'Search indexes should be driver-specific or abstracted behind a search service.',
            ],
            [
                'topic'     => 'Isolation defaults',
                'mysql'     => 'InnoDB defaults to REPEATABLE READ.',
                'postgres'  => 'Defaults to READ COMMITTED.',
                'appImpact' => 'Concurrent payment/application behavior can differ under load tests.',
            ],
        ];
    }
}
