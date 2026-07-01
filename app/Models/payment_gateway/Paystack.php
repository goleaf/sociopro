<?php

namespace App\Models\payment_gateway;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class Paystack extends Model
{
    use HasFactory;



public function payment_status($identifier = "") {

    // Start common code of all payment gateway
    $payment_details = session('payment_details');
    $payment_gateway = DB::table('payment_gateways')->where('identifier', $identifier)->first();
    $keys = json_decode($payment_gateway->keys, true);
    $test_mode = $payment_gateway->test_mode == 1 ? 1 : 0;
   
    // Ended common code of all payment gateway
    if($test_mode == 1){
        $secret_key = $keys['secret_test_key'];  
    } else {
        $secret_key = $keys['secret_live_key'];
    }
    $reference = isset($_GET['reference']) ? $_GET['reference'] : '';

    // Initialize cURL session
    $curl = curl_init();

    // Set cURL options
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.paystack.co/transaction/verify/".$reference,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer ".$secret_key,
        "Cache-Control: no-cache",
      ),
      // Add SSL verification options
      CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification (not recommended for production)
      // CURLOPT_CAINFO => '/path/to/ca-certificates.crt', // Optionally, specify the CA certificate file
    ));

    // Execute the cURL request
    $response = curl_exec($curl);
    
    // Check for errors
    $err = curl_error($curl);
    
    // Close cURL session
    curl_close($curl);
    
    // Handle errors
    if ($err) {
      // cURL error occurred
      echo "cURL Error: " . $err;
      return false;
    } else {
      // Process the response
      // Here you can handle the response from the Paystack API
      return true;
    }
}












}
