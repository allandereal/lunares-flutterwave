<?php

namespace Lunar\Flutterwave\Concerns;

interface ConstructsWebhookEvent
{
    public function constructEvent(string $jsonPayload, string $signature, string $secret);
}
