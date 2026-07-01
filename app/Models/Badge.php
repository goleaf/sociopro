<?php

namespace App\Models;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Session;

class Badge extends Model
{
    use HasFactory;
    protected $table = 'batchs';

    public function getUser(){
        return $this->belongsTo(User::class,'user_id');
    }



    
    public static function add_payment_success($identifier, $transaction_keys = array())
    {
        $payment_details = session('payment_details');
  
        $transaction_keys = json_encode($transaction_keys);
        $data['status'] = 1;
        $data['icon'] = 'fa-circle-check';
        $data['start_date'] = $payment_details['custom_field']['start_date'];
        $dateString = $payment_details['custom_field']['end_date'];
        $newTimestamp = strtotime($dateString);
        $newDateString = date('Y-m-d H:i:s', $newTimestamp);
        $data['end_date'] = $newDateString ;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['user_id'] = auth()->user()->id;
        $data['title'] =  $payment_details['items'][0]['title'];
        $data['description'] =   $payment_details['items'][0]['subtitle'];
         DB::table('batchs')->updateOrInsert(['user_id' => $data['user_id']], $data);
        

        $payment_data['item_type'] = 'badge';
    
        $payment_data['item_id'] = $payment_details['items'][0]['id'];
        $payment_data['user_id'] = auth()->user()->id;
        $payment_data['amount'] = $payment_details['payable_amount'];
        $payment_data['identifier'] = $identifier;
        $payment_data['transaction_keys'] = $transaction_keys;
        $payment_data['currency'] = get_settings('system_currency');
        $payment_data['created_at'] = date('Y-m-d H:i:s');
        $payment_data['updated_at'] = date('Y-m-d H:i:s');
        DB::table('payment_histories')->insert($payment_data);
     
         session(['payment_details' => array()]);
        Session::flash('success_message', get_phrase('Payment successfully done!'));
        return redirect()->route('badge');
    }



    

}
