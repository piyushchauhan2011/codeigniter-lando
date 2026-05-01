<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\JobModel;
use CodeIgniter\RESTful\ResourceController;

class JobsApi extends ResourceController
{
    protected $modelName = JobModel::class;

    public function index()
    {
        $rows = model(JobModel::class, false)
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->findAll(100);

        return $this->respond(['jobs' => $rows]);
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

        return $this->respond(['job' => $row]);
    }
}
