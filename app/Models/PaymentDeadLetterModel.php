<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class PaymentDeadLetterModel extends Model
{
    protected $table         = 'portal_payment_dead_letters';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'payment_intent_id',
        'job_id',
        'employer_user_id',
        'reason',
        'error_message',
        'payload',
        'resolved_at',
    ];
    protected $useTimestamps = true;

    public function findForIntent(int $intentId): ?array
    {
        $row = model(static::class, false)
            ->where('payment_intent_id', $intentId)
            ->orderBy('created_at', 'DESC')
            ->first();

        return $row !== null ? $row : null;
    }
}
