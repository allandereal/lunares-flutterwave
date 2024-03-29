<?php

namespace Lunar\Flutterwave;

use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Exceptions\DisallowMultipleCartOrdersException;
use Lunar\Models\Transaction;
use Lunar\PaymentTypes\AbstractPayment;
use Lunar\Flutterwave\Actions\UpdateOrderFromIntent;
use Lunar\Flutterwave\Facades\FlutterwaveFacade;
use Flutterwave\Exception\InvalidRequestException;
use Flutterwave\PaymentIntent;
use Flutterwave\Flutterwave;

class FlutterwavePaymentType extends AbstractPayment
{
    /**
     * The Flutterwave instance.
     *
     * @var \Flutterwave\FlutterwaveClient
     */
    protected $flutterwave;

    /**
     * The Payment intent.
     */
    protected PaymentIntent $paymentIntent;

    /**
     * The policy when capturing payments.
     *
     * @var string
     */
    protected $policy;

    /**
     * Initialise the payment type.
     */
    public function __construct()
    {
        $this->flutterwave = FlutterwaveFacade::getClient();

        $this->policy = config('lunar.flutterwave.policy', 'automatic');
    }

    /**
     * Authorize the payment for processing.
     */
    final public function authorize(): PaymentAuthorize
    {
        $this->order = $this->cart->draftOrder ?: $this->cart->completedOrder;

        if (! $this->order) {
            try {
                $this->order = $this->cart->createOrder();
            } catch (DisallowMultipleCartOrdersException $e) {
                return new PaymentAuthorize(
                    success: false,
                    message: $e->getMessage(),
                );
            }
        }

        $paymentIntentId = $this->data['payment_intent'];

        $this->paymentIntent = $this->flutterwave->paymentIntents->retrieve(
            $paymentIntentId
        );

        if (! $this->paymentIntent) {
            return new PaymentAuthorize(
                success: false,
                message: 'Unable to locate payment intent',
                orderId: $this->order->id,
            );
        }

        if ($this->paymentIntent->status == PaymentIntent::STATUS_REQUIRES_CAPTURE && $this->policy == 'automatic') {
            $this->paymentIntent = $this->flutterwave->paymentIntents->capture(
                $this->data['payment_intent']
            );
        }

        if ($this->cart) {
            if (! ($this->cart->meta['payment_intent'] ?? null)) {
                $this->cart->update([
                    'meta' => [
                        'payment_intent' => $this->paymentIntent->id,
                    ],
                ]);
            } else {
                $this->cart->meta['payment_intent'] = $this->paymentIntent->id;
                $this->cart->save();
            }
        }

        $order = (new UpdateOrderFromIntent)->execute(
            $this->order,
            $this->paymentIntent
        );

        return new PaymentAuthorize(
            success: (bool) $order->placed_at,
            message: $this->paymentIntent->last_payment_error,
            orderId: $order->id
        );
    }

    /**
     * Capture a payment for a transaction.
     *
     * @param  int  $amount
     */
    public function capture(Transaction $transaction, $amount = 0): PaymentCapture
    {
        $payload = [];

        if ($amount > 0) {
            $payload['amount_to_capture'] = $amount;
        }

        $charge = FlutterwaveFacade::getCharge($transaction->reference);

        $paymentIntent = FlutterwaveFacade::fetchIntent($charge->payment_intent);

        try {
            $response = $this->flutterwave->paymentIntents->capture(
                $paymentIntent->id,
                $payload
            );
        } catch (InvalidRequestException $e) {
            return new PaymentCapture(
                success: false,
                message: $e->getMessage()
            );
        }

        UpdateOrderFromIntent::execute($transaction->order, $paymentIntent);

        return new PaymentCapture(success: true);
    }

    /**
     * Refund a captured transaction
     *
     * @param  string|null  $notes
     */
    public function refund(Transaction $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        $charge = FlutterwaveFacade::getCharge($transaction->reference);

        try {
            $refund = $this->flutterwave->refunds->create(
                ['payment_intent' => $charge->payment_intent, 'amount' => $amount]
            );
        } catch (InvalidRequestException $e) {
            return new PaymentRefund(
                success: false,
                message: $e->getMessage()
            );
        }

        $transaction->order->transactions()->create([
            'success' => $refund->status != 'failed',
            'type' => 'refund',
            'driver' => 'flutterwave',
            'amount' => $refund->amount,
            'reference' => $refund->payment_intent,
            'status' => $refund->status,
            'notes' => $notes,
            'card_type' => $transaction->card_type,
            'last_four' => $transaction->last_four,
        ]);

        return new PaymentRefund(
            success: true
        );
    }
}
