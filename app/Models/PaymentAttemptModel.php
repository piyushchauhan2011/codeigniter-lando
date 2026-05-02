<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class PaymentAttemptModel extends Model
{
    protected $table         = 'portal_payment_attempts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'payment_intent_id',
        'attempt_number',
        'status',
        'scenario',
        'provider_reference',
        'error_message',
    ];
    protected $useTimestamps = true;

    /**
     * @return list<array<string, mixed>>
     */
    public function findForIntent(int $intentId): array
    {
        return model(static::class, false)
            ->where('payment_intent_id', $intentId)
            ->orderBy('attempt_number', 'ASC')
            ->findAll();
    }
}
