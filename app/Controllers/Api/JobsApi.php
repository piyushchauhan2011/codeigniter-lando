<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controllers\Api;

use App\Models\JobModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\RESTful\ResourceController;
use Throwable;

class JobsApi extends ResourceController
{
    protected $modelName = JobModel::class;

    public function index()
    {
        $rows = model(JobModel::class, false)
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->findAll(100);

        $jobs = array_map(fn (array $row): array => $this->withIsoTimestamps($row), $rows);

        return $this->respond(['jobs' => $jobs]);
    }

    public function show($id = null)
    {
        if ($id === null) {
            return $this->failNotFound('Job not found');
        }

        $row = model(JobModel::class, false)->where('status', 'published')->find((int) $id);

        if ($row === null) {
            return $this->failNotFound('Job not found');
        }

        return $this->respond(['job' => $this->withIsoTimestamps($row)]);
    }

    /**
     * @param array<string, mixed> $job
     *
     * @return array<string, mixed>
     */
    private function withIsoTimestamps(array $job): array
    {
        foreach (['created_at', 'updated_at'] as $field) {
            if (! empty($job[$field])) {
                try {
                    $job[$field . '_iso'] = Time::parse((string) $job[$field], 'UTC')->format('c');
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
