<?php

namespace Lunar\Flutterwave\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Facades\Payments;
use Lunar\Models\Cart;
use Lunar\Flutterwave\Concerns\ConstructsWebhookEvent;
use Flutterwave\Exception\SignatureVerificationException;
use Flutterwave\Exception\UnexpectedValueException;

final class WebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
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
            Log::error(
                $error = $e->getMessage()
            );
            return response(status: 400)->json([
                'webhook_successful' => false,
                'message' => $error,
            ]);
        }

        $paymentIntent = $event->data->object->id;

        $cart = Cart::where('meta->payment_intent', '=', $paymentIntent)->first();

        if (! $cart) {
            Log::error(
                $error = "Unable to find cart with intent ${paymentIntent}"
            );

            return response(status: 400)->json([
                'webhook_successful' => false,
                'message' => $error,
            ]);
        }

        $payment = Payments::driver('flutterwave')->cart($cart->calculate())->withData([
            'payment_intent' => $paymentIntent,
        ])->authorize();

        PaymentAttemptEvent::dispatch($payment);

        return response()->json([
            'webhook_successful' => true,
            'message' => 'Webook handled successfully',
        ]);
    }
}
