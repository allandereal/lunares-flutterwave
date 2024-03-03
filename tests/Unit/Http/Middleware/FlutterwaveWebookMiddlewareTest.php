<?php

uses(\Lunar\Flutterwave\Tests\Unit\TestCase::class)->group('lunar.flutterwave.middleware');

it('can handle valid event', function () {
    $this->app->bind(\Lunar\Flutterwave\Concerns\ConstructsWebhookEvent::class, function ($app) {
        return new class implements \Lunar\Flutterwave\Concerns\ConstructsWebhookEvent
        {
            public function constructEvent(string $jsonPayload, string $signature, string $secret)
            {
                return \Stripe\Event::constructFrom([]);
            }
        };
    });

    $request = \Illuminate\Http\Request::create('/flutterwave-webhook', 'POST');
    $request->headers->set('Flutterwave-Signature', 'foobar');
    $middleware = new \Lunar\Flutterwave\Http\Middleware\FlutterwaveWebhookMiddleware([]);

    $request = $middleware->handle($request, fn ($request) => $request);

    expect($request->status())->toBe(200);
});
