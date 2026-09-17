<?php

namespace App\Domain\Fulfillment\Actions;

use App\Domain\Fulfillment\Contracts\LicenseProvider;
use App\Domain\Fulfillment\Data\LicenseProvisioningRequest;
use App\Domain\Fulfillment\Enums\FulfillmentUnitStatus;
use App\Exceptions\Domain\Fulfillment\Exceptions\AmbiguousLicenseProvisioningException;
use App\Models\Credential;
use App\Models\Entitlement;
use App\Models\FulfillmentAttempt;
use App\Models\FulfillmentUnit;
use App\Models\ProviderMirror;
use App\Notifications\LicenseProvisionedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProvisionFulfillmentUnitAction
{
    public function __construct(private readonly LicenseProvider $provider) {}

    public function handle(FulfillmentUnit $unit): Entitlement
    {
        return Cache::lock('fulfillment-unit:'.$unit->getKey(), 600)->block(5, function () use ($unit): Entitlement {
            $existingEntitlement = $unit->entitlement()->first();

            if ($existingEntitlement !== null) {
                return $existingEntitlement;
            }

            $attempt = DB::transaction(function () use ($unit): FulfillmentAttempt {
                $lockedUnit = FulfillmentUnit::query()->lockForUpdate()->findOrFail($unit->getKey());
                $attemptNumber = ((int) $lockedUnit->attempts()->max('attempt_number')) + 1;

                $lockedUnit->forceFill([
                    'status' => FulfillmentUnitStatus::Processing,
                    'last_error' => null,
                ])->save();

                return $lockedUnit->attempts()->create([
                    'attempt_number' => $attemptNumber,
                    'status' => 'started',
                    'started_at' => now(),
                ]);
            });

            try {
                $unit->loadMissing(['order.billingAddress', 'order.user', 'product']);
                $summary = (array) $unit->meta;
                $result = $this->provider->provision(new LicenseProvisioningRequest(
                    idempotencyKey: $unit->idempotency_key,
                    productIdentifier: (string) ($summary['provider_product_id']
                        ?? $summary['keygen_product_id']
                        ?? $unit->product?->public_id
                        ?? $unit->product_id),
                    orderReference: (string) ($unit->order?->reference ?? $unit->order_id),
                    customerEmail: (string) ($unit->order?->billingAddress?->contact_email ?? $unit->order?->user?->email ?? ''),
                    metadata: [
                        'fulfillment_unit_id' => $unit->getKey(),
                        'order_id' => $unit->order_id,
                    ],
                    licensePolicyId: isset($summary['keygen_policy_id']) ? (string) $summary['keygen_policy_id'] : null,
                ));
            } catch (AmbiguousLicenseProvisioningException $exception) {
                try {
                    $result = $this->provider->findByIdempotencyKey($unit->idempotency_key);
                } catch (Throwable $recoveryException) {
                    $this->markNeedsAttention($unit, $attempt, $recoveryException);

                    throw $recoveryException;
                }

                if ($result === null) {
                    $this->markNeedsAttention($unit, $attempt, $exception);

                    throw $exception;
                }
            } catch (Throwable $exception) {
                $this->markNeedsAttention($unit, $attempt, $exception);

                throw $exception;
            }

            $entitlement = DB::transaction(function () use ($unit, $attempt, $result): Entitlement {
                $lockedUnit = FulfillmentUnit::query()->lockForUpdate()->with('order')->findOrFail($unit->getKey());
                $entitlement = $lockedUnit->entitlement()->first();

                if ($entitlement === null) {
                    $entitlement = Entitlement::query()->create([
                        'user_id' => $lockedUnit->order->user_id,
                        'order_id' => $lockedUnit->order_id,
                        'order_line_id' => $lockedUnit->order_line_id,
                        'product_id' => $lockedUnit->product_id,
                        'fulfillment_unit_id' => $lockedUnit->getKey(),
                        'type' => $lockedUnit->type,
                        'status' => 'active',
                        'provider' => $lockedUnit->provider,
                        'external_id' => $result->externalId,
                        'meta' => $result->metadata,
                    ]);

                    Credential::query()->create([
                        'entitlement_id' => $entitlement->getKey(),
                        'type' => 'license_key',
                        'secret' => $result->licenseKey,
                    ]);
                }

                ProviderMirror::query()->updateOrCreate(
                    [
                        'fulfillment_unit_id' => $lockedUnit->getKey(),
                        'operation' => 'provision',
                    ],
                    [
                        'provider' => $lockedUnit->provider,
                        'idempotency_key' => $lockedUnit->idempotency_key,
                        'external_id' => $result->externalId,
                        'status' => 'completed',
                        'response' => $result->metadata,
                    ],
                );

                $attempt->forceFill([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'provider_request_id' => $result->externalId,
                ])->save();

                $lockedUnit->forceFill([
                    'status' => FulfillmentUnitStatus::Completed,
                    'external_id' => $result->externalId,
                    'completed_at' => now(),
                ])->save();

                return $entitlement->load('credential');
            });

            if ($entitlement->wasRecentlyCreated) {
                $entitlement->load('user');
                $entitlement->user?->notify(new LicenseProvisionedNotification($entitlement->getKey()));
            }

            return $entitlement;
        });
    }

    private function markNeedsAttention(
        FulfillmentUnit $unit,
        FulfillmentAttempt $attempt,
        Throwable $exception,
    ): void {
        DB::transaction(function () use ($unit, $attempt, $exception): void {
            $lockedUnit = FulfillmentUnit::query()->lockForUpdate()->find($unit->getKey());

            if ($lockedUnit === null) {
                return;
            }

            $attempt->forceFill([
                'status' => 'failed',
                'error_class' => $exception::class,
                'error_message' => Str::limit($exception->getMessage(), 2000, ''),
                'completed_at' => now(),
            ])->save();

            $lockedUnit->forceFill([
                'status' => FulfillmentUnitStatus::NeedsAttention,
                'last_error' => Str::limit($exception->getMessage(), 2000, ''),
            ])->save();
        });
    }
}
