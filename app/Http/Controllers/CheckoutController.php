<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Lunar\Core\Facades\CartSession;
use Lunar\Core\Facades\Payments;
use Lunar\Core\Models\Customer;
use Lunar\Core\Models\Order;

class CheckoutController extends Controller
{
    public function show(): RedirectResponse
    {
        return redirect()->route('cart.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $cart = CartSession::current();

        abort_unless($cart && $cart->lines->isNotEmpty(), 404);

        $name = Str::of($request->user()->name)->squish();
        $email = Str::lower(trim((string) $request->user()->email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors([
                'checkout' => 'Please update your account with a valid email address before checking out.',
            ]);
        }

        $customer = $request->user()->latestCustomer() ?? Customer::create([
            'first_name' => $name->before(' ')->toString(),
            'last_name' => $name->contains(' ') ? $name->after(' ')->toString() : '',
        ]);

        if (! $customer->users()->whereKey($request->user()->getKey())->exists()) {
            $customer->users()->attach($request->user());
        }

        $cart->setCustomer($customer);
        $payment = Payments::driver('chapa')->withData([
            'contact_email' => $email,
            'first_name' => $name->before(' ')->toString(),
            'last_name' => $name->contains(' ') ? $name->after(' ')->toString() : '',
        ])->cart($cart)->authorize();

        if (! $payment->success || ! $payment->orderId) {
            return back()->withErrors(['checkout' => $payment->message ?: 'The order could not be placed.']);
        }

        $checkoutUrl = data_get(Order::query()->find($payment->orderId)?->meta, 'chapa.checkout_url');

        if (blank($checkoutUrl)) {
            return back()->withErrors(['checkout' => 'The payment checkout could not be started.']);
        }

        return redirect()->away($checkoutUrl);
    }

    public function complete(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->getKey() && ! $order->isDraft(), 404);

        return view('storefront.checkout-return', [
            'order' => $order->load('lines.purchasable.product'),
        ]);
    }
}
