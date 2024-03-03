<p align="center"><img src="https://github.com/lunarphp/addons/assets/1488016/59ab3f7a-3a3f-4519-a14d-254525dbcf78" width="300" ></p>


<p align="center">This addon enables Flutterwave payments on your Lunar storefront.</p>

## Alpha Release

This addon is currently in Alpha, whilst every step is taken to ensure this is working as intended, it will not be considered out of Alpha until more tests have been added and proved.

## Tests required:

- [ ] Successful charge response from Flutterwave.
- [ ] Unsuccessful charge response from Flutterwave.
- [ ] Test `manual` config reacts appropriately.
- [x] Test `automatic` config reacts appropriately.
- [ ] Ensure transactions are stored correctly in the database
- [x] Ensure that the payment intent is not duplicated when using the same Cart
- [ ] Ensure appropriate responses are returned based on Flutterwave's responses.
- [ ] Test refunds and partial refunds create the expected transactions
- [ ] Make sure we can manually release a payment or part payment and handle the different responses.

## Minimum Requirements

- Lunar >= `0.6`
- A [Flutterwave](http://flutterwave.com/) account with secret and public keys

## Optional Requirements

- Laravel Livewire (if using frontend components)
- Alpinejs (if using frontend components)
- Javascript framework

## Installation

### Require the composer package

```sh
composer require allandereal/lunares-flutterwave
```

### Publish the configuration

This will publish the configuration under `config/getcandy/flutterwave.php`.

```sh
php artisan vendor:publish --tag=getcandy.flutterwave.config
```

### Publish the views (optional)

Lunar Flutterwave comes with some helper components for you to use on your checkout, if you intend to edit the views they provide, you can publish them.

```sh
php artisan vendor:publish --tag=getcandy.flutterwave.components
```

### Enable the driver

Set the driver in `config/getcandy/payments.php`

```php
<?php

return [
    // ...
    'types' => [
        'card' => [
            // ...
            'driver' => 'flutterwave',
        ],
    ],
];
```

### Add your Flutterwave credentials

Make sure you have the Flutterwave credentials set in `config/services.php`

```php
'flutterwave' => [
    'key' => env('FLUTTERWAVE_SECRET'),
    'public_key' => env('FLUTTERWAVE_PK'),
],
```

> Keys can be found in your Flutterwave account https://dashboard.flutterwave.com/apikeys

## Configuration

Below is a list of the available configuration options this package uses in `config/getcandy/flutterwave.php`

| Key | Default | Description |
| --- | --- | --- |
| `policy` | `automatic` | Determines the policy for taking payments and whether you wish to capture the payment manually later or take payment straight away. Available options `manual` or `automatic` |

---

## Backend Usage

### Create a PaymentIntent

```php
use \Lunar\Flutterwave\Facades\Flutterwave;

Flutterwave::createIntent(\Lunar\Models\Cart $cart);
```

This method will create a Flutterwave PaymentIntent from a Cart and add the resulting ID to the meta for retrieval later. If a PaymentIntent already exists for a cart this will fetch it from Flutterwave and return that instead to avoid duplicate PaymentIntents being created.

```php
$paymentIntentId = $cart->meta['payment_intent']; // The resulting ID from the method above.
```
```php
$cart->meta->payment_intent;
```

### Fetch an existing PaymentIntent

```php
use \Lunar\Flutterwave\Facades\Flutterwave;

Flutterwave::fetchIntent($paymentIntentId);
```

### Syncing an existing intent

If a payment intent has been created and there are changes to the cart, you will want to update the intent so it has the correct totals.

```php
use \Lunar\Flutterwave\Facades\Flutterwave;

Flutterwave::syncIntent(\Lunar\Models\Cart $cart);
```

## Webhooks

The plugin provides a webhook you will need to add to Flutterwave. You can read the guide on how to do this on the Flutterwave website [https://flutterwave.com/docs/webhooks/quickstart](https://flutterwave.com/docs/webhooks/quickstart).

The 3 events you should listen to are `payment_intent.payment_failed`,`payment_intent.processing`,`payment_intent.succeeded`. 

The path to the webhook will be `http:://yoursite.com/flutterwave/webhook`.

You can customise the path for the webhook in `config/lunar/flutterwave.php`.

You will also need to add the webhook signing secret to the `services.php` config file:

```php
<?php

return [
    // ...
    'flutterwave' => [
        // ...
        'webhooks' => [
            'payment_intent' => '...'
        ],
    ],
];
```

## Storefront Examples

First we need to set up the backend API call to fetch or create the intent, this isn't Vue specific but will likely be different if you're using Livewire.

```php
use \Lunar\Flutterwave\Facades\Flutterwave;

Route::post('api/payment-intent', function () {
    $cart = CartSession::current();

    $cartData = CartData::from($cart);

    if ($paymentIntent = $cartData->meta['payment_intent'] ?? false) {
        $intent = FlutterwaveFacade::fetchIntent($paymentIntent);
    } else {
        $intent = FlutterwaveFacade::createIntent($cart);
    }

    if ($intent->amount != $cart->total->value) {
        FlutterwaveFacade::syncIntent($cart);
    }
        
    return $intent;
})->middleware('web');
```

### Vuejs

This is just using Flutterwave's payment elements, for more information [check out the Flutterwave guides](https://flutterwave.com/docs/payments/elements)

### Payment component

```js
<script setup>
const { VITE_FLUTTERWAVE_PK } = import.meta.env

const flutterwave = Flutterwave(VITE_FLUTTERWAVE_PK)
const flutterwaveElements = ref({})

const buildForm = async () => {
    const { data } = await axios.post("api/payment-intent")

    flutterwaveElements.value = flutterwave.elements({
        clientSecret: data.client_secret,
    })

    const paymentElement = flutterwaveElements.value.create("payment", {
        layout: "tabs",
        defaultValues: {
            billingDetails: {
                name: `${billingAddress.value.first_name} ${billingAddress.value?.last_name}`,
                phone: billingAddress.value?.contact_phone,
            },
        },
        fields: {
            billingDetails: "never",
        },
    })

    paymentElement.mount("#payment-element")
}

onMounted(async () => {
    await buildForm()
})

// The address object can be either passed through as props or via a second API call, but it should likely come from the cart.

const submit = async () => {
    try {
        const address = {...}

        const { error } = await flutterwave.confirmPayment({
            //`Elements` instance that was used to create the Payment Element
            elements: flutterwaveElements.value,
            confirmParams: {
                return_url: 'http://yoursite.com/checkout/complete',
                payment_method_data: {
                    billing_details: {
                        name: `${address.first_name} ${address.last_name}`,
                        email: address.contact_email,
                        phone: address.contact_phone,
                        address: {
                            city: address.city,
                            country: address.country.iso2,
                            line1: address.line_one,
                            line2: address.line_two,
                            postal_code: address.postcode,
                            state: address.state,
                        },
                    },
                },
            },
        })
    } catch (e) {
    
    }
}
</script>
```

```html
<template>
    <form @submit.prevent="submit">
        <div id="payment-element">
            <!--Flutterwave.js injects the Payment Element-->
        </div>
    </form>
</template>
```
---

## Contributing

Contributions are welcome, if you are thinking of adding a feature, please submit an issue first so we can determine whether it should be included.


## Testing

A [MockClient](https://github.com/getcandy/flutterwave/blob/main/tests/Flutterwave/MockClient.php) class is used to mock responses the Flutterwave API will return.
