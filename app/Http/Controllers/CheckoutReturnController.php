<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutReturnController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Order $order): View
    {
        abort_unless(
            $request->user()
            && ($order->buyer_user_id === $request->user()->id || $order->buyer_email === $request->user()->email),
            404,
        );

        return view('storefront.checkout-return', [
            'order' => $order->load('payments'),
            'title' => 'Payment status',
        ]);
    }
}
