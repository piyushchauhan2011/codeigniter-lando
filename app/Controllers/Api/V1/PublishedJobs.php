<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\JobModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class PublishedJobs extends ResourceController
{
    protected $modelName = JobModel::class;

    public function index()
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $per  = min(50, max(1, (int) ($this->request->getGet('per_page') ?? 20)));

        $model = model(JobModel::class, false);

        $total = $model->where('status', 'published')->countAllResults();

        $rows = $model
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->limit($per, ($page - 1) * $per)
            ->findAll();

        $jobs = array_map(fn (array $row): array => $this->withIsoTimestamps($row), $rows);

        $totalPages = $total > 0 ? (int) ceil($total / $per) : 0;

        return $this->respond([
            'data' => ['jobs' => $jobs],
            'meta' => [
                'page'        => $page,
                'per_page'    => $per,
                'total'       => $total,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    public function show($id = null)
    {
        if ($id === null) {
            return $this->respond([
                'error' => [
                    'code'    => 'not_found',
                    'message' => 'Job not found',
                ],
            ], 404);
        }

        $row = model(JobModel::class, false)->where('status', 'published')->find((int) $id);

        if ($row === null) {
            return $this->respond([
                'error' => [
                    'code'    => 'not_found',
                    'message' => 'Job not found',
                ],
            ], 404);
        }

        return $this->respond([
            'data' => ['job' => $this->withIsoTimestamps($row)],
            'meta' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $job
     *
     * @return array<string, mixed>
     */
    private function withIsoTimestamps(array $job): array
    {
        foreach (['created_at', 'updated_at'] as $field) {
            $value = $job[$field] ?? null;
            if (is_string($value) && $value !== '') {
                try {
                    $job[$field . '_iso'] = Time::parse($value, 'UTC')->format('c');
                } catch (Throwable) {
                    $job[$field . '_iso'] = null;
                }
            } else {
                $job[$field . '_iso'] = null;
            }
        }

        return $job;
    }
}
