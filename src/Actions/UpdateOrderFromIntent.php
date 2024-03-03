<?php

namespace Lunar\Flutterwave\Actions;

use Illuminate\Support\Facades\DB;
use Lunar\Models\Order;
use Lunar\Flutterwave\Facades\FlutterwaveFacade;
use Flutterwave\PaymentIntent;

class UpdateOrderFromIntent
{
    final public static function execute(
        Order $order,
        PaymentIntent $paymentIntent,
        string $successStatus = 'paid',
        string $failStatus = 'failed'
    ): Order {
        return DB::transaction(function () use ($order, $paymentIntent) {

            $charges = FlutterwaveFacade::getCharges($paymentIntent->id);

            $order = app(StoreCharges::class)->store($order, $charges);
            $requiresCapture = $paymentIntent->status === PaymentIntent::STATUS_REQUIRES_CAPTURE;

            $statuses = config('lunar.flutterwave.status_mapping', []);

            $placedAt = null;

            if ($paymentIntent->status === PaymentIntent::STATUS_SUCCEEDED) {
                $placedAt = now();
            }

            if ($charges->isEmpty() && !$requiresCapture) {
                return $order;
            }

            $order->update([
                'status' => $statuses[$paymentIntent->status] ?? $paymentIntent->status,
                'placed_at' => $order->placed_at ?: $placedAt,
            ]);

            return $order;
        });
    }
}
