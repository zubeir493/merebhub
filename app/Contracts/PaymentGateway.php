<?php

namespace App\Contracts;

use App\Models\Order;
use App\Payments\CheckoutSession;
use App\Payments\VerifiedPayment;

interface PaymentGateway
{
    public function initializeCheckout(Order $order): CheckoutSession;

    public function verifyTransaction(string $reference): VerifiedPayment;
}
