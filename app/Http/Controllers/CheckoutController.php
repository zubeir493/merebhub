<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Lunar\Core\Facades\CartSession;
use Lunar\Core\Facades\Payments;
use Lunar\Core\Models\Country;
use Lunar\Core\Models\Customer;
use Lunar\Core\Models\Order;

class CheckoutController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $cart = CartSession::current();

        if (! $cart || $cart->lines->isEmpty()) {
            return redirect()->route('cart.index');
        }

        return view('storefront.checkout', [
            'cart' => $cart,
            'country' => Country::query()->where('iso3', 'ETH')->firstOrFail(),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'line_one' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:50'],
            'country_id' => ['required', 'integer', 'exists:lunar_countries,id'],
        ]);
        $cart = CartSession::current();

        abort_unless($cart && $cart->lines->isNotEmpty(), 404);

        $customer = $request->user()->latestCustomer() ?? Customer::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? '',
        ]);

        if (! $customer->users()->whereKey($request->user()->getKey())->exists()) {
            $customer->users()->attach($request->user());
        }

        $cart->setCustomer($customer);
        $cart->setBillingAddress($validated);
        $payment = Payments::driver('cash-in-hand')->cart($cart)->authorize();

        if (! $payment->success || ! $payment->orderId) {
            return back()->withErrors(['checkout' => $payment->message ?: 'The order could not be placed.']);
        }

        CartSession::forget(delete: false);

        return redirect()->route('checkout.complete', $payment->orderId);
    }

    public function complete(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->getKey() && ! $order->isDraft(), 404);

        return view('storefront.checkout-return', [
            'order' => $order->load('lines.purchasable.product'),
        ]);
    }
}
