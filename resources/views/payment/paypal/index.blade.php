<div id="smart-button-container">
  <div style="text-align: center;">
    <div id="paypal-button-container"></div>
  </div>
</div>
<script src="https://www.paypalobjects.com/api/checkout.js"></script>
<!--<script src="https://www.paypal.com/sdk/js?client-id={{$client_id}}&enable-funding=venmo&currency={{get_settings('system_currency')}}" data-sdk-integration-source="button-factory"></script>-->
<script>
  function initPayPalButton() {
          paypal.Button.render({
            env: '{{ ($payment_gateway->test_mode != 1) ? 'production':'sandbox' }}', // 'sandbox' or 'production'
            style: {
              label: 'paypal',
              size:  'large',    // small | medium | large | responsive
              shape: 'rect',     // pill | rect
              color: 'blue',     // gold | blue | silver | black
              tagline: true
            },
            client: {
              sandbox:    '{{ $client_id }}',
              production: '{{ $client_id }}'
            },
        
            commit: true, // Show a 'Pay Now' button
        
            payment: function(data, actions) {
              return actions.payment.create({
                payment: {
                  transactions: [
                    {
                      amount: { total: '{{ $payment_details['payable_amount'] }}', currency: '{{ $systemCurrency }}' }
                    }
                  ]
                }
              });
            },
            onAuthorize: function(data, actions) {
              // executes the payment
              return actions.payment.execute().then(function() {
                // PASSING TO CONTROLLER FOR CHECKING
                //console.log(data)
                window.location = "{{$payment_details['success_url'].'/'.$payment_gateway->identifier}}"+"?payment_id="+data.paymentID+"&payment_token="+data.paymentToken+"&payer_id="+data.payerID
              });
            }
        
          }, '#paypal-button-container');
  }


  $(function(){
    const initPaypal = setInterval(function(){
      if(typeof paypal !== 'undefined'){
        initPayPalButton();
        clearInterval(initPaypal);
      }
    },500);
  });
  

  
</script>
