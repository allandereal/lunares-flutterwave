<?php

namespace Lunar\Flutterwave\DataTransferObjects;

use Lunar\Models\Order;
use Stripe\PaymentIntent;

class OrderIntent
{
    public function __construct(
        public Order $order,
        public PaymentIntent $paymentIntent
    ) {
    }
}
