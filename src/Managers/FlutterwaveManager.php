<?php

namespace Lunar\Flutterwave\Managers;

use Illuminate\Support\Collection;
use Lunar\Flutterwave\Rave\FlutterwaveClient;
use Lunar\Flutterwave\Rave\FlutterwaveTransaction;
use Lunar\Models\Cart;
use Stripe\Exception\InvalidRequestException;

class FlutterwaveManager
{
    public function __construct()
    {
        //
    }

    /**
     * Return the Flutterwave client
     */
    public function getClient(): FlutterwaveClient
    {
        return new FlutterwaveClient(
            //config('services.flutterwave.key')
        );
    }

    /**
     * Create a payment transaction from a Cart
     */
    public function createTransaction(Cart $cart): FlutterwaveTransaction
    {
        $shipping = $cart->shippingAddress;

        $meta = (array) $cart->meta;

        if ($meta && ! empty($meta['transaction_id'])) {
            $transaction = $this->fetchTransaction(
                $meta['transaction_id']
            );

            if ($transaction) {
                return $transaction;
            }
        }

        $transaction = $this->buildTransaction(
            $cart->total->value,
            $cart->currency->code,
            $shipping,
        );

        if (! $meta) {
            $cart->update([
                'meta' => [
                    'transaction_id' => $transaction->id,
                ],
            ]);
        } else {
            $meta['transaction_id'] = $transaction->id;
            $cart->meta = $meta;
            $cart->save();
        }

        return $transaction;
    }

    public function syncTransaction(Cart $cart)
    {
        $meta = (array) $cart->meta;

        if (empty($meta['transaction_id'])) {
            return;
        }

        $cart = $cart->calculate();

        $this->getClient()->transactions->update(
            $meta['transaction_id'],
            ['amount' => $cart->total->value]
        );
    }

    /**
     * Fetch a transaction from the Flutterwave API.
     *
     * @param  string  $transactionId
     * @return null|FlutterwaveTransaction
     */
    public function fetchTransaction($transactionId)
    {
        try {
            $transaction = FlutterwaveTransaction::retrieve($transactionId);
        } catch (InvalidRequestException $e) {
            return null;
        }

        return $transaction;
    }

    public function getCharges(string $transactionId): Collection
    {
        try {
            return collect(
                $this->getClient()->charges->all([
                    'transaction_id' => $transactionId,
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
     * Build the transaction
     *
     * @param  int  $value
     * @param  string  $currencyCode
     * @param  \Lunar\Models\CartAddress  $shipping
     */
    protected function buildTransaction($value, $currencyCode, $shipping): FlutterwaveTransaction
    {
        return FlutterwaveTransaction::create([
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
