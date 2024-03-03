<?php

namespace Lunar\Flutterwave\Managers;

use Illuminate\Support\Collection;
use Lunar\Models\Cart;
use Stripe\Charge;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\StripeClient;

class FlutterwaveManager
{
    public function __construct()
    {
        Flutterwave::setApiKey(config('services.flutterwave.key'));
    }

    /**
     * Return the Flutterwave client
     */
    public function getClient(): FlutterwaveClient
    {
        return new FlutterwaveClient(
            config('services.flutterwave.key')
        );
    }

    /**
     * Create a payment intent from a Cart
     *
     * @return \Flutterwave\PaymentIntent
     */
    public function createIntent(Cart $cart)
    {
        $shipping = $cart->shippingAddress;

        $meta = (array) $cart->meta;

        if ($meta && ! empty($meta['payment_intent'])) {
            $intent = $this->fetchIntent(
                $meta['payment_intent']
            );

            if ($intent) {
                return $intent;
            }
        }

        $paymentIntent = $this->buildIntent(
            $cart->total->value,
            $cart->currency->code,
            $shipping,
        );

        if (! $meta) {
            $cart->update([
                'meta' => [
                    'payment_intent' => $paymentIntent->id,
                ],
            ]);
        } else {
            $meta['payment_intent'] = $paymentIntent->id;
            $cart->meta = $meta;
            $cart->save();
        }

        return $paymentIntent;
    }

    public function syncIntent(Cart $cart)
    {
        $meta = (array) $cart->meta;

        if (empty($meta['payment_intent'])) {
            return;
        }

        $cart = $cart->calculate();

        $this->getClient()->paymentIntents->update(
            $meta['payment_intent'],
            ['amount' => $cart->total->value]
        );
    }

    /**
     * Fetch an intent from the Flutterwave API.
     *
     * @param  string  $intentId
     * @return null|\Flutterwave\PaymentIntent
     */
    public function fetchIntent($intentId)
    {
        try {
            $intent = PaymentIntent::retrieve($intentId);
        } catch (InvalidRequestException $e) {
            return null;
        }

        return $intent;
    }

    public function getCharges(string $paymentIntentId): Collection
    {
        try {
            return collect(
                $this->getClient()->charges->all([
                    'payment_intent' => $paymentIntentId,
                ])['data'] ?? null
            );
        } catch (\Exception $e) {
            //
        }

        return collect();
    }

    public function getCharge($chargeId)
    {
        return $this->getClient()->charges->retrieve($chargeId);
    }

    /**
     * Build the intent
     *
     * @param  int  $value
     * @param  string  $currencyCode
     * @param  \Lunar\Models\CartAddress  $shipping
     * @return \Flutterwave\PaymentIntent
     */
    protected function buildIntent($value, $currencyCode, $shipping)
    {
        return PaymentIntent::create([
            'amount' => $value,
            'currency' => $currencyCode,
            'automatic_payment_methods' => ['enabled' => true],
            'capture_method' => config('lunar.flutterwave.policy', 'automatic'),
            'shipping' => [
                'name' => "{$shipping->first_name} {$shipping->last_name}",
                'address' => [
                    'city' => $shipping->city,
                    'country' => $shipping->country->iso2,
                    'line1' => $shipping->line_one,
                    'line2' => $shipping->line_two,
                    'postal_code' => $shipping->postcode,
                    'state' => $shipping->state,
                ],
            ],
        ]);
    }
}
