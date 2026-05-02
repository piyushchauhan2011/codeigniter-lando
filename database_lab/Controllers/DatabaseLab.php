<?php

declare(strict_types=1);

namespace DatabaseLab\Controllers;

use App\Controllers\BaseController;
use DatabaseLab\Models\DatabaseLabModel;

class DatabaseLab extends BaseController
{
    public function index(): string
    {
        $model = new DatabaseLabModel();

        $filters = [
            'q'               => $this->request->getGet('q'),
            'location'        => $this->request->getGet('location'),
            'employment_type' => $this->request->getGet('employment_type'),
            'category_id'     => $this->request->getGet('category_id'),
        ];

        $employer = (int) ($this->request->getGet('employer') ?? 123);
        $job      = (int) ($this->request->getGet('job') ?? 456);
        $location = (string) ($this->request->getGet('shard_location') ?? 'Remote');

        return view('DatabaseLab\Views\index', [
            'title'              => lang('DatabaseLab.page_title'),
            'indexing'           => $model->indexingAnalysis($filters),
            'transactions'       => $model->transactionLab(),
            'locks'              => $model->lockLab(),
            'replication'        => $model->replicationExamples(),
            'shardRoute'         => $model->shardRoute($employer, $job, $location),
            'dialectComparisons' => $model->mysqlPostgresDifferences(),
        ]);
    }
}
