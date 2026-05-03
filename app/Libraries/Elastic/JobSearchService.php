<?php

declare(strict_types=1);

namespace App\Libraries\Elastic;

use App\Models\JobModel;
use Config\Elastic;

class JobSearchService
{
    public function __construct(
        private readonly ElasticClient $client,
        private readonly Elastic $config,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function createIndex(bool $replace = false): array
    {
        if ($replace) {
            $this->client->deleteIndex($this->config->jobIndex);
        }

        return $this->client->createIndex($this->config->jobIndex, [
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'jobs_text' => [
                            'type'      => 'custom',
                            'tokenizer' => 'standard',
                            'filter'    => ['lowercase', 'asciifolding'],
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'id'              => ['type' => 'integer'],
                    'title'           => ['type' => 'text', 'analyzer' => 'jobs_text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                    'description'     => ['type' => 'text', 'analyzer' => 'jobs_text'],
                    'location'        => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                    'employment_type' => ['type' => 'keyword'],
                    'category_id'     => ['type' => 'integer'],
                    'category_name'   => ['type' => 'keyword'],
                    'company_name'    => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                    'salary_min'      => ['type' => 'integer'],
                    'salary_max'      => ['type' => 'integer'],
                    'status'          => ['type' => 'keyword'],
                    'is_featured'     => ['type' => 'boolean'],
                    'created_at'      => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss||strict_date_optional_time'],
                    'updated_at'      => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss||strict_date_optional_time'],
                ],
            ],
        ]);
    }

    public function reindexPublishedJobs(): int
    {
        $rows = $this->publishedRows();
        if ($rows === []) {
            return 0;
        }

        $bulkBody = [];
        foreach ($rows as $row) {
            $doc = $this->documentFromRow($row);
            $bulkBody[] = ['index' => ['_index' => $this->config->jobIndex, '_id' => (string) $doc['id']]];
            $bulkBody[] = $doc;
        }

        $this->client->bulk($bulkBody, true);

        return count($rows);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{total:int, hits:list<array<string,mixed>>, aggregations:array<string,mixed>}
     */
    public function search(array $filters, int $page = 1, int $perPage = 10): array
    {
        $must   = [['term' => ['status' => 'published']]];
        $filter = [];

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            $must[] = [
                'multi_match' => [
                    'query'     => $term,
                    'fields'    => ['title^3', 'description', 'company_name', 'location'],
                    'fuzziness' => 'AUTO',
                ],
            ];
        }

        foreach (['employment_type', 'category_id'] as $field) {
            $value = trim((string) ($filters[$field] ?? ''));
            if ($value !== '') {
                $filter[] = ['term' => [$field => $field === 'category_id' ? (int) $value : $value]];
            }
        }

        $location = trim((string) ($filters['location'] ?? ''));
        if ($location !== '') {
            $must[] = ['match' => ['location' => $location]];
        }

        $body = [
            'from'  => max(0, ($page - 1) * $perPage),
            'size'  => $perPage,
            'query' => [
                'bool' => [
                    'must'   => $must,
                    'filter' => $filter,
                ],
            ],
            'sort' => [
                ['is_featured' => ['order' => 'desc']],
                ['created_at' => ['order' => 'desc']],
            ],
            'aggs' => [
                'employment_types' => ['terms' => ['field' => 'employment_type']],
                'categories'       => ['terms' => ['field' => 'category_name']],
                'locations'        => ['terms' => ['field' => 'location.keyword']],
            ],
        ];

        $result = $this->client->search($this->config->jobIndex, $body);
        $hits   = $result['hits']['hits'] ?? [];

        return [
            'total'        => (int) ($result['hits']['total']['value'] ?? 0),
            'hits'         => array_map(static fn (array $hit): array => $hit['_source'] ?? [], $hits),
            'aggregations' => is_array($result['aggregations'] ?? null) ? $result['aggregations'] : [],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function publishedRows(): array
    {
        return model(JobModel::class, false)
            ->select('portal_jobs.*, employer_profiles.company_name, job_categories.name AS category_name')
            ->join('employer_profiles', 'employer_profiles.user_id = portal_jobs.employer_user_id', 'left')
            ->join('job_categories', 'job_categories.id = portal_jobs.category_id', 'left')
            ->where('portal_jobs.status', 'published')
            ->findAll();
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function documentFromRow(array $row): array
    {
        return [
            'id'              => (int) $row['id'],
            'title'           => (string) $row['title'],
            'description'     => (string) $row['description'],
            'location'        => (string) $row['location'],
            'employment_type' => (string) $row['employment_type'],
            'category_id'     => $row['category_id'] === null ? null : (int) $row['category_id'],
            'category_name'   => (string) ($row['category_name'] ?? 'Uncategorized'),
            'company_name'    => (string) ($row['company_name'] ?? ''),
            'salary_min'      => $row['salary_min'] === null ? null : (int) $row['salary_min'],
            'salary_max'      => $row['salary_max'] === null ? null : (int) $row['salary_max'],
            'status'          => (string) $row['status'],
            'is_featured'     => (bool) ($row['is_featured'] ?? false),
            'created_at'      => (string) $row['created_at'],
            'updated_at'      => (string) $row['updated_at'],
        ];
    }
}
