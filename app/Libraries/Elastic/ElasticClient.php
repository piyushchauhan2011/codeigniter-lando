<?php

declare(strict_types=1);

namespace App\Libraries\Elastic;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper around {@see Client} (elastic/elasticsearch-php) for the ELK lab.
 */
class ElasticClient
{
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function clusterHealth(): array
    {
        try {
            return $this->client->cluster()->health()->asArray();
        } catch (Throwable $e) {
            throw $this->wrap($e);
        }
    }

    /**
     * @param array<string, mixed> $body Index settings + mappings (Elasticsearch create index API body)
     *
     * @return array<string, mixed>
     */
    public function createIndex(string $index, array $body): array
    {
        try {
            return $this->client->indices()->create([
                'index' => $index,
                'body'  => $body,
            ])->asArray();
        } catch (Throwable $e) {
            throw $this->wrap($e);
        }
    }

    /**
     * Deletes an index; ignores HTTP 404 when the index is missing.
     */
    public function deleteIndex(string $index): void
    {
        try {
            $this->client->indices()->delete(['index' => $index]);
        } catch (ClientResponseException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                return;
            }

            throw $this->wrap($e);
        } catch (Throwable $e) {
            throw $this->wrap($e);
        }
    }

    /**
     * @param list<array<string, mixed>> $bulkBody Alternating bulk action lines and document lines (elasticsearch-php format)
     *
     * @return array<string, mixed>
     */
    public function bulk(array $bulkBody, bool $refresh = true): array
    {
        try {
            $params = ['body' => $bulkBody];
            if ($refresh) {
                $params['refresh'] = 'true';
            }

            return $this->client->bulk($params)->asArray();
        } catch (Throwable $e) {
            throw $this->wrap($e);
        }
    }

    /**
     * @param array<string, mixed> $body Search request body (query, sort, aggs, …)
     *
     * @return array<string, mixed>
     */
    public function search(string $index, array $body): array
    {
        try {
            return $this->client->search([
                'index' => $index,
                'body'  => $body,
            ])->asArray();
        } catch (Throwable $e) {
            throw $this->wrap($e);
        }
    }

    private function wrap(Throwable $e): RuntimeException
    {
        return new RuntimeException($e->getMessage(), $e->getCode(), $e);
    }
}
