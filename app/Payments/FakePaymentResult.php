<?php

declare(strict_types=1);

namespace App\Payments;

final class FakePaymentResult
{
    public function __construct(
        public readonly string $providerReference,
        public readonly string $message,
    ) {
    }
}
