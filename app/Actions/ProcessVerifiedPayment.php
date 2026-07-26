<?php

namespace App\Actions;

use App\Enums\BillingInterval;
use App\Enums\BillingModel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\ProvisionOrderLicenseJob;
use App\Models\Earning;
use App\Models\Order;
use App\Models\ProductPlan;
use App\Models\Subscription;
use App\Payments\VerifiedPayment;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProcessVerifiedPayment
{
    public function handle(Order $order, VerifiedPayment $verified): bool
    {
        $shouldFulfill = DB::transaction(function () use ($order, $verified): bool {
            $lockedOrder = Order::query()
                ->with(['payments', 'items.productPlan', 'items.renewalSubscription'])
                ->lockForUpdate()
                ->findOrFail($order->id);
            $payment = $lockedOrder->payments
                ->firstWhere('provider_reference', $lockedOrder->transaction_reference);

            if (! $payment) {
                throw new RuntimeException('The Chapa payment record is missing.');
            }

            if ($lockedOrder->status === OrderStatus::Paid) {
                return false;
            }

            if (
                ! $verified->isSuccessful()
                || $verified->transactionReference !== $lockedOrder->transaction_reference
                || $verified->amountMinor !== $lockedOrder->total_minor
                || $verified->currency !== 'ETB'
            ) {
                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'verification_payload' => $verified->payload,
                    'failed_at' => now(),
                ]);
                $lockedOrder->update([
                    'status' => OrderStatus::PaymentFailed,
                    'payment_failed_at' => now(),
                ]);

                return false;
            }

            $payment->update([
                'provider_payment_id' => $verified->providerPaymentId,
                'status' => PaymentStatus::Successful,
                'verification_payload' => $verified->payload,
                'verified_at' => now(),
            ]);
            $lockedOrder->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'payment_failed_at' => null,
            ]);

            foreach ($lockedOrder->items as $item) {
                $authorId = $item->primary_author_snapshot['id'] ?? null;

                if ($authorId) {
                    Earning::firstOrCreate(
                        ['order_item_id' => $item->id, 'author_id' => $authorId],
                        [
                            'order_id' => $lockedOrder->id,
                            'product_id' => $item->product_id,
                            'currency' => 'ETB',
                            'gross_minor' => $item->unit_amount_minor,
                            'discount_minor' => $item->discount_minor,
                            'net_minor' => $item->total_minor,
                            'platform_share_minor' => $item->platform_share_minor,
                            'author_share_minor' => $item->author_share_minor,
                            'refund_deduction_minor' => 0,
                            'final_author_earnings_minor' => $item->author_share_minor,
                            'status' => 'pending',
                            'earned_at' => now(),
                        ],
                    );
                }

                if ($item->productPlan?->billing_model === BillingModel::ManualSubscription) {
                    $previousSubscription = $item->renewalSubscription;
                    $startsAt = $previousSubscription?->expires_at?->isFuture()
                        ? $previousSubscription->expires_at->copy()
                        : now();
                    $subscription = Subscription::firstOrCreate(
                        ['order_item_id' => $item->id],
                        [
                            'public_id' => (string) Str::uuid(),
                            'customer_id' => $lockedOrder->buyer_user_id,
                            'product_id' => $item->product_id,
                            'product_plan_id' => $item->product_plan_id,
                            'previous_subscription_id' => $previousSubscription?->id,
                            'status' => SubscriptionStatus::Active,
                            'starts_at' => $startsAt,
                            'expires_at' => $this->expirationDate($startsAt, $item->productPlan),
                        ],
                    );

                    if ($subscription->wasRecentlyCreated && $previousSubscription) {
                        $previousSubscription->update(['status' => SubscriptionStatus::Renewed]);
                    }
                }
            }

            return true;
        });

        if ($shouldFulfill) {
            ProvisionOrderLicenseJob::dispatch($order->id);
        }

        return $shouldFulfill;
    }

    private function expirationDate(CarbonInterface $startsAt, ProductPlan $plan): CarbonInterface
    {
        if ($plan->license_duration_days) {
            return $startsAt->copy()->addDays($plan->license_duration_days);
        }

        return match ($plan->billing_interval) {
            BillingInterval::Weekly => $startsAt->copy()->addWeek(),
            BillingInterval::Monthly => $startsAt->copy()->addMonthNoOverflow(),
            BillingInterval::Quarterly => $startsAt->copy()->addMonthsNoOverflow(3),
            BillingInterval::Biannual => $startsAt->copy()->addMonthsNoOverflow(6),
            BillingInterval::Yearly => $startsAt->copy()->addYearNoOverflow(),
            null => $startsAt->copy()->addMonthNoOverflow(),
        };
    }
}
