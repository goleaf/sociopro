<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\PaymentHistoryEntry;

class PaymentHistory extends Controller
{
    private const PER_PAGE = 25;

    public function index()
    {
        $query = PaymentHistoryEntry::query()->orderByDesc('id');

        if (auth()->user()->user_role == UserRole::Admin->value) {
            $payment_histories = $query->paginate(self::PER_PAGE);
        } else {
            $payment_histories = $query
                ->where('user_id', auth()->user()->id)
                ->paginate(self::PER_PAGE);
        }

        $page_data['payment_histories'] = $payment_histories;
        $page_data['view_path'] = 'payment_history.index';

        return view('backend.index', $page_data);
    }
}
