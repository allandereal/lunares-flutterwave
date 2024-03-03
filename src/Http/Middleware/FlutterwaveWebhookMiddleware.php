<?php

namespace Lunar\Flutterwave\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lunar\Flutterwave\Concerns\ConstructsWebhookEvent;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;

class FlutterwaveWebhookMiddleware
{
    public function handle(Request $request, Closure $next = null)
    {
        $secret = config('services.flutterwave.webhooks.payment_intent');
        $flutterwaveSig = $request->header('Flutterwave-Signature');

        try {
            $event = app(ConstructsWebhookEvent::class)->constructEvent(
                $request->getContent(),
                $flutterwaveSig,
                $secret
            );
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            abort(400, $e->getMessage());
        }

        if (! in_array(
            $event->type,
            [
                'payment_intent.canceled',
                'payment_intent.created',
                'payment_intent.payment_failed',
                'payment_intent.processing',
                'payment_intent.succeeded',
            ]
        )) {
            return response('', 200);
        }

        return $next($request);
    }
}
