<?php

namespace Lunar\Flutterwave\DataTransferObjects;

use Lunar\Models\Order;
use Flutterwave\PaymentIntent;

class OrderIntent
{
    public function __construct(
        public Order $order,
        public PaymentIntent $paymentIntent
    ) {
    }
}
