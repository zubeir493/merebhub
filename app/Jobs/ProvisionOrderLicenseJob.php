<?php

namespace App\Jobs;

use App\Actions\ProvisionOrderLicense;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProvisionOrderLicenseJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public array $backoff = [10, 60, 300, 900];

    public function __construct(public int $orderId)
    {
        $this->afterCommit();
    }

    public function handle(ProvisionOrderLicense $provision): void
    {
        $provision->handle(Order::findOrFail($this->orderId));
    }

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    public function failed(?Throwable $exception): void
    {
        Order::whereKey($this->orderId)->update([
            'fulfillment_error' => $exception?->getMessage() ?? 'License fulfillment failed.',
        ]);

        Log::error('License fulfillment failed', [
            'order_id' => $this->orderId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
