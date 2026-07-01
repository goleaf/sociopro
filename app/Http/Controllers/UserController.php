<?php

namespace App\Http\Controllers;

use App\Models\Sponsor;
use App\Support\Files\FileUploader;
use App\Support\Validation\DateTimeRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function dashboard()
    {
        $page_data['view_path'] = 'dashboard';

        return view('backend.index', $page_data);
    }

    public function ads()
    {
        $page_data['ads'] = Sponsor::where('user_id', auth()->user()->id)->get();
        $page_data['view_path'] = 'ads';

        return view('backend.index', $page_data);
    }

    public function ad_create()
    {
        $page_data['view_path'] = 'ad_create';

        return view('backend.index', $page_data);
    }

    public function ad_store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255|string',
            'image' => ['required', 'mimes:jpg,jpeg,png'],
        ]);

        $data['status'] = 1;
        $data['user_id'] = auth()->user()->id;
        $data['name'] = $request->name;
        $data['description'] = $request->description;
        $data['ext_url'] = $request->ext_url;
        $data['image'] = random(40).'.'.$request->image->extension();
        Sponsor::insert($data);
        FileUploader::upload($request->image, 'public/storage/sponsor/thumbnail/'.$data['image'], 300);

        flash()->addSuccess('New ad created successfully');

        return redirect()->route('user.ads');
    }

    public function ad_edit($id)
    {
        $page_data['ad'] = Sponsor::where('id', $id)->where('user_id', auth()->user()->id)->first();
        $page_data['view_path'] = 'ad_edit';

        return view('backend.index', $page_data);
    }

    public function ad_update($id, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255|string',
        ]);
        $query = Sponsor::where('id', $id)->where('user_id', auth()->user()->id);
        $data['name'] = $request->name;
        $data['description'] = $request->description;
        $data['ext_url'] = $request->ext_url;

        if ($request->image) {
            $data['image'] = random(40).'.'.$request->image->extension();
            remove_file('public/storage/sponsor/thumbnail/'.$query->first()->image);
        }

        $query->update($data);

        if ($request->image) {
            FileUploader::upload($request->image, 'public/storage/sponsor/thumbnail/'.$data['image'], 300);
        }

        flash()->addSuccess('Ad updated successfully');

        return redirect()->route('user.ads');
    }

    public function ad_delete($id)
    {
        $query = Sponsor::where('id', $id)->where('user_id', auth()->user()->id);

        remove_file('public/storage/sponsor/thumbnail/'.$query->first()->image);

        $query->delete();
        flash()->addSuccess('Ad deleted successfully');

        return redirect()->back();
    }

    public function ad_activation($id, Request $request)
    {
        $page_data['ad'] = Sponsor::where('id', $id)->where('user_id', auth()->user()->id)->first();
        $page_data['view_path'] = 'ad_edit';

        return view('backend.index', $page_data);
    }

    public function ad_charge_by_daterange(Request $request)
    {
        $rules = [
            'start_date' => DateTimeRules::requiredBrowserDate(),
            'end_date' => DateTimeRules::requiredDateRangeEnd('start_date'),
        ];
        $validator = Validator::make($request->only(array_keys($rules)), $rules);

        if ($validator->fails()) {
            return 0;
        }

        $totalDays = DateTimeRules::browserDate($request->start_date)
            ->diffInDays(DateTimeRules::browserDate($request->end_date));

        return ($totalDays + 1) * get_settings('ad_charge_per_day');
    }

    public function payment_configuration($id, Request $request)
    {
        $request->validate([
            'start_date' => DateTimeRules::requiredBrowserDate(),
            'end_date' => DateTimeRules::requiredDateRangeEnd('start_date'),
        ]);

        $total_days = DateTimeRules::browserDate($request->start_date)
            ->diffInDays(DateTimeRules::browserDate($request->end_date));
        $payable_amount = ($total_days * get_settings('ad_charge_per_day')) + get_settings('ad_charge_per_day');

        $payment_details = [
            'items' => [
                [
                    'id' => $id,
                    'title' => get_phrase('Ad Activation for ____ days', [$total_days]),
                    'subtitle' => get_phrase('Your ad will be published on ____', [$request->start_date]),
                    'price' => $payable_amount,
                    'discount_price' => $payable_amount,
                    'discount_percentage' => 0,
                ],
            ],
            'custom_field' => [
                'start_date' => DateTimeRules::browserDateAtCurrentTime($request->start_date),
                'end_date' => DateTimeRules::browserDateAtCurrentTime($request->end_date),
                'user_id' => auth()->user()->id,
            ],
            'success_method' => [
                'model_name' => 'Sponsor',
                'function_name' => 'add_payment_success',
            ],
            'tax' => 0,
            'coupon' => null,
            'payable_amount' => $payable_amount,
            'cancel_url' => route('user.ads'),
            'success_url' => route('payment.success', ''),
        ];
        session(['payment_details' => $payment_details]);

        return redirect()->route('payment');
    }
}
