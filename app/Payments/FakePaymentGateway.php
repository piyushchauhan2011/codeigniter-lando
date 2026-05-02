<?php

declare(strict_types=1);

namespace App\Payments;

final class FakePaymentGateway
{
    /**
     * @param array<string, mixed> $intent
     */
    public function charge(array $intent, int $attemptNumber): FakePaymentResult
    {
        $scenario = (string) ($intent['scenario'] ?? 'success');

        if ($scenario === 'permanent_fail') {
            throw new PermanentPaymentException('Card declined by the fake gateway.');
        }

        if ($scenario === 'transient_fail' && $attemptNumber < 3) {
            throw new TransientPaymentException('Fake gateway timeout. Retry the payment later.');
        }

        if ($scenario === 'exhaust_retries') {
            throw new TransientPaymentException('Fake gateway timeout persisted until retries were exhausted.');
        }

        $reference = sprintf('fake_%s_%02d', (string) $intent['idempotency_key'], $attemptNumber);

        return new FakePaymentResult($reference, 'Fake payment captured.');
    }
}
