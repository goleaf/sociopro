<!-- Include Paystack Inline JS -->
<script src="https://js.paystack.co/v1/inline.js"></script> 

<!-- Button to initiate payment -->
<button type="button" class="btn btn-primary py-2 px-3" onclick="payWithPaystack()">{{ __('Pay by Paystack') }}</button>

@php
// Start common code of all payment gateway
$keys = json_decode($payment_gateway->keys, true);
$test_mode = $payment_gateway->test_mode == 1 ? 1 : 0;

// Ended common code of all payment gateway
if ($test_mode == 1) {
    $key = $keys['public_test_key'];
    // $secret_key = $keys['secret_test_key'];
} else {
    $key = $keys['public_live_key'];
    // $secret_key = $keys['secret_live_key'];
}

$amount = $payment_details['items'][0]['price'];
$user_details = Auth::user();

@endphp

<!-- JavaScript function to handle Paystack payment -->
<script>

    function payWithPaystack() {
        var handler = PaystackPop.setup({
            key: '{{ $key }}',
            email: '{{ $user_details->email }}',
            amount: '{{ $amount * 100 }}',
            currency: "{{ $payment_gateway->currency }}",
            metadata: {
                custom_fields: [
                    {
                        display_name: "{{ $user_details->first_name }} {{ $user_details->last_name }}",
                        variable_name: "paid_on",
                        value: '{{ route('make.payment', $payment_gateway->identifier) }}'
                    }
                ]
            },
            callback: function(response) {
                    window.location.replace('{{ $payment_details['success_url'] }}/{{ $payment_gateway->identifier }}?reference=' + response.reference);
                },
            onClose: function() {
                window.location.replace('{{ $payment_details['cancel_url'] }}');
            }
        });
        handler.openIframe();
    }








</script>


