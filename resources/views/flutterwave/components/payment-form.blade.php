<div x-data="{
  flutterwave: null,
  policy: @entangle('policy'),
  paymentElement: null,
  processing: false,
  error: null,
  handleSubmit() {
    this.processing = true
    this.error = null

    address = {
      city: '{{ addslashes($this->billing->city) }}',
      country: '{{ addslashes($this->billing->country->iso2) }}',
      line1: '{{ addslashes($this->billing->line_one) }}',
      line2: '{{ addslashes($this->billing->line_two) }}',
      postal_code: '{{ addslashes($this->billing->postcode) }}',
      state: '{{ addslashes($this->billing->state) }}',
    }

    FlutterwaveCheckout({
      public_key: '{{ config('services.flutterwave.public_key') }}',
      tx_ref: 'txref-{{ \Illuminate\Support\Str::random(9).time() }}',
      amount: {{ $this->cart->total }},
      currency: '{{ $this->cart->currency->code }}',
      payment_options: 'mobilemoneyuganda, card, mpesa, banktransfer, ussd',
      redirect_url: '{{ $returnUrl ?: url()->current() }}',
      meta: {
        source: 'docs-inline-test',
        consumer_mac: '92a3-912ba-1192a',
      },
      customer: {
        email: '{{ $this->billing->contact_email }}',
        phone_number: '{{ $this->billing->contact_phone }}',
        name: '{{ $this->billing->first_name }} {{ $this->billing->last_name }}',
      },
      customizations: {
        title: 'Beisome Store',
        description: 'Checkout at Beisome Store',
        logo: 'https://checkout.flutterwave.com/assets/img/rave-logo.png',
      },
      callback: function (data){
        console.log('payment callback:', data);
      },
      onclose: function() {
        console.log('Payment cancelled!');
      }
    });

{{--    this.flutterwave.confirmPayment({--}}
{{--        elements,--}}
{{--        confirmParams: {--}}
{{--          // Make sure to change this to your payment completion page--}}
{{--          return_url: '{{ $returnUrl ?: url()->current() }}',--}}
{{--          payment_method_data: {--}}
{{--            billing_details: {--}}
{{--              name: '{{ $this->billing->first_name }} {{ $this->billing->last_name }}',--}}
{{--              email: '{{ $this->billing->contact_email }}',--}}
{{--              phone: '{{ $this->billing->contact_phone }}',--}}
{{--              address--}}
{{--            }--}}
{{--          }--}}
{{--        },--}}
{{--      }).then(result => {--}}
{{--        if (result.error) {--}}
{{--          this.error = result.error.message--}}
{{--          this.processing = false--}}
{{--        }--}}
{{--      }).catch(error => {--}}
{{--        this.processing = false--}}
{{--      })--}}
  },
  init() {
{{--    this.flutterwave = Flutterwave('{{ config('services.flutterwave.public_key')}}');--}}

{{--    elements = this.flutterwave.elements({--}}
{{--      clientSecret: '{{ $this->clientSecret }}'--}}
{{--    });--}}

{{--    this.paymentElement = elements.create('payment', {--}}
{{--      fields: {--}}
{{--        billingDetails: 'never'--}}
{{--      }--}}
{{--    });--}}
{{--    this.paymentElement.mount(this.$refs.paymentElement);--}}
  }
}">
  <!-- Display a payment form -->
  <form x-ref="payment-form" x-on:submit.prevent="handleSubmit()">
    <div x-ref="paymentElement">
      <!--Flutterwave.js injects the Payment Element-->
    </div>
    <div class="mt-4">
      <button
        class="flex items-center px-5 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-500 disabled:opacity-50"
        type="submit"
        x-bind:disabled="processing"
      >
        <span
          x-show="!processing"
        >
          Make Payment
        </span>
        <span
          x-show="processing"
          class="block mr-2"
        >
          <svg
            class="w-5 h-5 text-white animate-spin"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
        </span>
        <span
          x-show="processing"
        >
          Processing
        </span>
      </button>
    </div>
    <div x-show="error" x-text="error" class="p-3 mt-4 text-sm text-red-600 rounded bg-red-50"></div>
  </form>
</div>