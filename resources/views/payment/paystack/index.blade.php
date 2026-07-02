<!-- Include Paystack Inline JS -->
<script src="https://js.paystack.co/v1/inline.js"></script> 

<!-- Button to initiate payment -->
<button type="button" class="btn btn-primary py-2 px-3" onclick="payWithPaystack()">{{ __('Pay by Paystack') }}</button>

<!-- JavaScript function to handle Paystack payment -->
<script>

    function payWithPaystack() {
        var handler = PaystackPop.setup({
            key: '{{ $key }}',
            email: '{{ $user_details->email }}',
            amount: '{{ $amount_minor }}',
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


