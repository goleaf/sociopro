<?php

namespace App\Http\Controllers;

use App\Actions\Badges\BuildBadgePageDataAction;
use App\Support\Validation\DateTimeRules;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function badge(Request $request, BuildBadgePageDataAction $buildBadgePageData)
    {
        return view('frontend.index', $buildBadgePageData->index($request->user()));
    }

    public function badge_info(Request $request, BuildBadgePageDataAction $buildBadgePageData)
    {
        return view('frontend.index', $buildBadgePageData->confirmation($request->user()));
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
                'user_id' => $request->user()->id,
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
