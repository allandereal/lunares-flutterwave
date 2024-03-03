<?php

use Lunar\Flutterwave\Facades\FlutterwaveFacade;
use Lunar\Flutterwave\Tests\Utils\CartBuilder;

uses(\Lunar\Flutterwave\Tests\Unit\TestCase::class);

it('can create a payment intent', function () {
    $cart = CartBuilder::build();

    FlutterwaveFacade::createIntent($cart->calculate());

    expect($cart->refresh()->meta['payment_intent'])->toBe('pi_1DqH152eZvKYlo2CFHYZuxkP');
});
