<?php

declare(strict_types=1);

namespace App\Repositories\JobPortal;

use App\Models\PaymentIntentModel;

class PaymentIntentRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function latestByEmployerIndexedByJob(int $employerUserId): array
    {
        return model(PaymentIntentModel::class)->latestByEmployerIndexedByJob($employerUserId);
    }
}
