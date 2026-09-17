<?php

namespace App\Http\Controllers;

use App\Integrations\Chapa\ChapaPaymentVerificationException;
use App\Integrations\Chapa\VerifyChapaPaymentAction;
use App\Support\IntegrationSettingsStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lunar\Core\Facades\CartSession;
use Throwable;

class ChapaPaymentController extends Controller
{
    public function return(Request $request, VerifyChapaPaymentAction $verify): RedirectResponse
    {
        $transactionReference = $this->transactionReference($request);

        if (blank($transactionReference)) {
            return redirect()->route('cart.index')->withErrors([
                'payment' => 'The payment reference was missing.',
            ]);
        }

        try {
            $order = $verify->handle($transactionReference);
        } catch (Throwable $exception) {
            Log::warning('Chapa return verification failed.', [
                'transaction_reference' => $transactionReference,
                'exception' => $exception::class,
            ]);

            return redirect()->route('cart.index')->withErrors([
                'payment' => 'We could not verify your payment yet. Please try again shortly.',
            ]);
        }

        CartSession::forget(delete: false);

        return redirect()->route('checkout.complete', $order);
    }

    public function webhook(Request $request, VerifyChapaPaymentAction $verify, IntegrationSettingsStore $settings): JsonResponse
    {
        $secret = (string) $settings->get('chapa', 'webhook_secret', config('services.chapa.webhook_secret'));
        $signatures = array_filter([
            $request->header('x-chapa-signature'),
            $request->header('chapa-signature'),
        ]);
        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        if (blank($secret) || $signatures === []
            || ! collect($signatures)->contains(fn (string $signature): bool => hash_equals($expectedSignature, $signature))) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $transactionReference = $this->transactionReference($request);

        if (blank($transactionReference)) {
            return response()->json(['message' => 'A transaction reference is required.'], 422);
        }

        try {
            $verify->handle($transactionReference);
        } catch (ChapaPaymentVerificationException $exception) {
            Log::notice('Chapa webhook received before payment verification succeeded.', [
                'transaction_reference' => $transactionReference,
            ]);

            return response()->json(['status' => 'pending'], 202);
        } catch (Throwable $exception) {
            Log::warning('Chapa webhook processing failed.', [
                'transaction_reference' => $transactionReference,
                'exception' => $exception::class,
            ]);

            return response()->json(['status' => 'pending'], 202);
        }

        return response()->json(['status' => 'ok']);
    }

    private function transactionReference(Request $request): string
    {
        return $request->string('tx_ref')->trim()->toString()
            ?: $request->string('trx_ref')->trim()->toString()
            ?: $request->string('data.tx_ref')->trim()->toString()
            ?: $request->string('data.trx_ref')->trim()->toString();
    }
}
