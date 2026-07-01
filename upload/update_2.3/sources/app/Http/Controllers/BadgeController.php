<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function badge(){
    
        $currentDate = Carbon::now();
    
        // $page_data['badge'] = Badge::whereDate('start_date', '<=', $currentDate)
        //     ->whereDate('end_date', '>=', $currentDate)
        //     ->orderBy('id', 'DESC')
        //     ->first();

        $page_data['badges'] = Badge::where('user_id', auth()->user()->id)
            ->orderBy('id', 'DESC')
            ->get();

        $page_data['view_path'] = 'frontend.badge.badge';
        return view('frontend.index', $page_data);
    }

    public function badge_info(){
        $page_data['view_path'] = 'frontend.badge.badge_info';
        return view('frontend.index', $page_data);
    }






    public function payment_configuration($id, Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);
       

        $badge_pay =  get_settings('badge_price');
        $title = $request->title;
        $description = $request->description;
        $start_timestamp = strtotime($request->start_date . ' ' . date('H:i:s'));
        $end_timestamp = strtotime('+30 days', $start_timestamp);
        $start_date = date('Y-m-d H:i:s', $start_timestamp);
        $end_date = date('Y-m-d H:i:s', $end_timestamp);
        
        $payment_details = [
            'items' => [
                [
                    'id' => $id,
                    'title' => $title,
                    'subtitle' => $description,
                    'price' => $badge_pay, 
                    'discount_price' => 0,
                    'discount_percentage' => 0,
                ]
            ],
            'custom_field' => [
                'start_date' => date('Y-m-d H:i:s', $start_timestamp),
                'end_date' => date('Y-m-d H:i:s', $end_timestamp),
                'user_id' => auth()->user()->id,
                'description' => $description,
            ],
            'success_method' => [
                'model_name' => 'Badge',
                'function_name' => 'add_payment_success',
            ],
            'tax' => 0,
            'coupon' => null,
            'payable_amount' => $badge_pay, 
            'cancel_url' => route('badge'),
            'success_url' => route('payment.success', ''),
        ];
        session(['payment_details' => $payment_details]);
        return redirect()->route('payment');
    }





}
