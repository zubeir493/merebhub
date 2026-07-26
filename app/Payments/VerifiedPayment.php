<?php

namespace App\Payments;

final readonly class VerifiedPayment
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $transactionReference,
        public string $providerPaymentId,
        public int $amountMinor,
        public string $currency,
        public string $status,
        public array $payload,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
}
