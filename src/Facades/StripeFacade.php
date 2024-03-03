<?php

namespace Lunar\Flutterwave\Facades;

use Illuminate\Support\Facades\Facade;

class FlutterwaveFacade extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return 'gc:flutterwave';
    }
}
