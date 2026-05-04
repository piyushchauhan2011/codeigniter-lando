<?php

declare(strict_types=1);

namespace App\Services\JobPortal;

use App\Repositories\JobPortal\JobRepository;
use App\Repositories\JobPortal\PaymentIntentRepository;

class EmployerDashboardService
{
    public function __construct(
        private readonly JobRepository $jobs,
        private readonly PaymentIntentRepository $paymentIntents,
    ) {
    }

    /**
     * @return array{jobs: list<array<string, mixed>>, paymentIntents: array<int, array<string, mixed>>}
     */
    public function buildDashboard(int $employerUserId): array
    {
        return [
            'jobs'           => $this->jobs->findAllByEmployerUserIdNewestFirst($employerUserId),
            'paymentIntents' => $this->paymentIntents->latestByEmployerIndexedByJob($employerUserId),
        ];
    }
}
