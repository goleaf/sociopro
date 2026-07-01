<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Support\Validation\DateTimeRules;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function badge()
    {
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

    public function badge_info()
    {
        $page_data['view_path'] = 'frontend.badge.badge_info';

        return view('frontend.index', $page_data);
    }

    public function payment_configuration($id, Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'start_date' => DateTimeRules::nullableBrowserDate(),
        ]);

        $badge_pay = get_settings('badge_price');
        $title = $request->title;
        $description = $request->description;
        $startDate = $request->filled('start_date')
            ? DateTimeRules::browserDateAtCurrentTime($request->start_date)
            : now(config('app.timezone'))->format('Y-m-d H:i:s');
        $endDate = Carbon::parse($startDate)->addDays(30)->format('Y-m-d H:i:s');

        $payment_details = [
            'items' => [
                [
                    'id' => $id,
                    'title' => $title,
                    'subtitle' => $description,
                    'price' => $badge_pay,
                    'discount_price' => 0,
                    'discount_percentage' => 0,
                ],
            ],
            'custom_field' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
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
