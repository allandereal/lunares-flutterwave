<?php

namespace Lunar\Flutterwave\Pipelines;

use Lunar\Flutterwave\DataTransferObjects\OrderIntent;

class UpdateOrderFromCharges
{
    public function handle(OrderIntent $orderIntent, \Closure $next)
    {

    }
}
