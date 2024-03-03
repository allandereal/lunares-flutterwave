<?php

uses(\Lunar\Flutterwave\Tests\Unit\TestCase::class);

it('creates pending transaction when status is requires_action', function () {

    $cart = \Lunar\Flutterwave\Tests\Utils\CartBuilder::build();

    $order = $cart->createOrder();

    $paymentIntent = \Lunar\Flutterwave\Facades\FlutterwaveFacade::getClient()
        ->paymentIntents
        ->retrieve('PI_REQUIRES_ACTION');

    $updatedOrder = \Lunar\Flutterwave\Actions\UpdateOrderFromIntent::execute($order, $paymentIntent);

    expect($updatedOrder->status)->toBe($order->status);
    expect($updatedOrder->placed_at)->toBeNull();
    expect($updatedOrder->refresh()->transactions)->toBeEmpty();
})->group('lunar.flutterwave.actions');
