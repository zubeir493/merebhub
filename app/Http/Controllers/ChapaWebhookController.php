<?php

namespace App\Http\Controllers;

use App\Actions\ProcessVerifiedPayment;
use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use RuntimeException;

class ChapaWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentGateway $gateway,
        ProcessVerifiedPayment $processPayment,
    ): JsonResponse {
        $this->verifySignature($request);

        $payload = $request->json()->all();
        $reference = (string) (
            Arr::get($payload, 'tx_ref')
            ?? Arr::get($payload, 'trx_ref')
            ?? Arr::get($payload, 'data.tx_ref')
            ?? ''
        );

        if ($reference === '') {
            throw new RuntimeException('Chapa webhook is missing the transaction reference.');
        }

        $order = Order::where('transaction_reference', $reference)->firstOrFail();
        $eventId = implode(':', array_filter([
            $reference,
            (string) (Arr::get($payload, 'status') ?? Arr::get($payload, 'data.status')),
            (string) (Arr::get($payload, 'ref_id') ?? Arr::get($payload, 'data.reference')),
        ]));
        $event = WebhookEvent::firstOrCreate(
            ['provider' => 'chapa', 'event_id' => $eventId],
            [
                'event_type' => 'payment.updated',
                'payload' => $payload,
                'attempts' => 0,
            ],
        );

        if ($event->processed_at) {
            return response()->json(['status' => 'already_processed']);
        }

        $event->increment('attempts');

        try {
            $verified = $gateway->verifyTransaction($reference);
            $processPayment->handle($order, $verified);
            $event->update(['processed_at' => now(), 'last_error' => null]);
        } catch (\Throwable $exception) {
            $event->update(['last_error' => $exception->getMessage()]);
            throw $exception;
        }

        return response()->json(['status' => 'accepted']);
    }

    private function verifySignature(Request $request): void
    {
        $secret = config('services.chapa.webhook_secret');

        abort_unless(is_string($secret) && $secret !== '', 503, 'Chapa webhook is not configured.');

        $payloadSignature = $request->header('x-chapa-signature');
        $secretSignature = $request->header('chapa-signature');
        $validPayloadSignature = is_string($payloadSignature)
            && hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $payloadSignature);
        $validSecretSignature = is_string($secretSignature)
            && hash_equals(hash_hmac('sha256', $secret, $secret), $secretSignature);

        abort_unless($validPayloadSignature || $validSecretSignature, 401, 'Invalid Chapa signature.');
    }
}
