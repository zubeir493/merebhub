<?php

namespace App\Http\Controllers;

use App\Integrations\Chapa\ChapaPaymentVerificationException;
use App\Integrations\Chapa\VerifyChapaPaymentAction;
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

    public function webhook(Request $request, VerifyChapaPaymentAction $verify): JsonResponse
    {
        $secret = (string) config('services.chapa.webhook_secret');
        $signature = (string) $request->header('x-chapa-signature');

        if (blank($secret) || blank($signature)
            || ! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
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
