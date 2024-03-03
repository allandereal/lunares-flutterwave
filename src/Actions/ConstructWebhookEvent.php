<?php

namespace Lunar\Flutterwave\Actions;

use Lunar\Flutterwave\Concerns\ConstructsWebhookEvent;
use Flutterwave\Webhook;

class ConstructWebhookEvent implements ConstructsWebhookEvent
{
    public function constructEvent(string $jsonPayload, string $signature, string $secret)
    {
        return Webhook::constructEvent(
            $jsonPayload, $signature, $secret
        );
    }
}
