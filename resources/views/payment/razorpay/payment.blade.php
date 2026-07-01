@php
    $page_data = $data['page_data'];
    $payment_details = $data['payment_details'];
    $color = $data['color'];
@endphp

<button id="rzp-button1" hidden>Pay</button>

<form action="{{ route('payment.success', ['identifier' => 'razorpay']) }}" hidden>
    @csrf
    <input type="text" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="text" name="razorpay_order_id" id="razorpay_order_id">
    <input type="text" name="razorpay_signature" id="razorpay_signature">
    <input type="submit" id="payment_done">
</form>

<script src="{{ asset('assets/frontend/js/jquery-3.6.0.min.js') }}"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    var color = "{{ $color }}";

    var options = {
        "key": "{{ $page_data['razorpay_id'] }}",
        "amount": "{{ $page_data['amount'] }}",
        "currency": "USD",

        "name": "{{ $page_data['name'] }}",
        "description": "{{ $page_data['description'] }}",
        "image": "{{ get_user_image($payment_details['custom_field']['user_id'], 'optimized') }}",

        "order_id": "{{ $page_data['order_id'] }}",

        "handler": function(response) {
            var razorpay_payment_id = response.razorpay_payment_id;
            var razorpay_order_id = response.razorpay_order_id;
            var razorpay_signature = response.razorpay_signature;

            $('#razorpay_payment_id').val(razorpay_payment_id);
            $('#razorpay_order_id').val(razorpay_order_id);
            $('#razorpay_signature').val(razorpay_signature);

            $('#payment_done').click();
        },

        "prefill": {
            "name": "{{ $page_data['name'] }}",
            "email": "{{ $page_data['email'] }}",
            "contact": "{{ $page_data['phone'] }}"
        },
        "notes": {
            "address": "{{ $page_data['name'] }}"
        },
        "theme": {
            "color": color
        }
    };
    var rzp1 = new Razorpay(options);
    rzp1.on('payment.failed', function(response) {
        alert(response.error.code);
        alert(response.error.description);
        alert(response.error.source);
        alert(response.error.step);
        alert(response.error.reason);
        alert(response.error.metadata.order_id);
        alert(response.error.metadata.payment_id);
    });
    document.getElementById('rzp-button1').onclick = function(e) {
        rzp1.open();
        e.preventDefault();
    }

    $(document).ready(function() {
        $('#rzp-button1').click();
    });
</script>
