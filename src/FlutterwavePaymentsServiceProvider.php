<?php

namespace Lunar\Flutterwave;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Lunar\Facades\Payments;
use Lunar\Flutterwave\Actions\ConstructWebhookEvent;
use Lunar\Flutterwave\Components\PaymentForm;
use Lunar\Flutterwave\Concerns\ConstructsWebhookEvent;
use Lunar\Flutterwave\Managers\FlutterwaveManager;

class FlutterwavePaymentsServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Register our payment type.
        Payments::extend('flutterwave', function ($app) {
            return $app->make(FlutterwavePaymentType::class);
        });

        $this->app->bind(ConstructsWebhookEvent::class, function ($app) {
            return $app->make(ConstructWebhookEvent::class);
        });

        $this->app->singleton('gc:flutterwave', function ($app) {
            return $app->make(FlutterwaveManager::class);
        });

        Blade::directive('flutterwaveScripts', function () {
            return <<<'EOT'
                <script src="https://checkout.flutterwave.com/v3.js"></script>
            EOT;
        });

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lunar');
        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');
        $this->mergeConfigFrom(__DIR__.'/../config/flutterwave.php', 'lunar.flutterwave');

        $this->publishes([
            __DIR__.'/../config/flutterwave.php' => config_path('lunar/flutterwave.php'),
        ], 'lunar.flutterwave.config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/lunar'),
        ], 'lunar.flutterwave.components');

        if (class_exists(Livewire::class)) {
            // Register the flutterwave payment component.
            Livewire::component('flutterwave.payment', PaymentForm::class);
        }
    }
}
