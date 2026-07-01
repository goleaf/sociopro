<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Session;

class Sponsor extends Model
{
    use HasFactory;

    public static function add_payment_success($identifier)
    {
        $payment_details = session('payment_details');
        $transaction_keys = session('keys');

        $transaction_keys = json_encode($transaction_keys);

        $data['paid_amount'] = $payment_details['payable_amount'];
        $data['status'] = 1;
        $data['start_date'] = $payment_details['custom_field']['start_date'];
        $data['end_date'] = $payment_details['custom_field']['end_date'];
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::table('sponsors')->where('id', $payment_details['items'][0]['id'])->update($data);

        $payment_data['item_type'] = 'ad';
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
        flash()->addSuccess('Payment successfully done!');
        return redirect()->route('user.ads');
    }
}
