<?php

namespace Lunar\Flutterwave\Rave;

class FlutterwaveTransaction
{
    public static function create(array $data)
    {
        return new self();
    }

    public static function retrieve($transactionId)
    {
        return new self();
    }
}