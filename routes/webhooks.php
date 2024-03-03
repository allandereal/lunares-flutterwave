<?php

use Illuminate\Support\Facades\Route;

Route::post(config('lunar.flutterwave.webhook_path', 'flutterwave/webhook'), \Lunar\Flutterwave\Http\Controllers\WebhookController::class)
    ->middleware([\Lunar\Flutterwave\Http\Middleware\FlutterwaveWebhookMiddleware::class, 'api'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])

    ->name('lunar.flutterwave.webhook');
