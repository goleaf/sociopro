<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Account_active_request;
use App\Models\User;
use App\ViewModels\BladeViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountStatusController extends Controller
{
    public function disabled(Request $request, BladeViewData $viewData): View
    {
        return view('frontend.disable_view', [
            'accountActivationRequest' => $viewData->accountActivationRequest($request->user()),
        ]);
    }

    public function requestEnable(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->is($user), 403);

        Account_active_request::updateOrCreate(
            ['user_id' => $user->id],
            ['status' => 'pending']
        );

        flash()->addSuccess('Account enable request successfully');

        return redirect()->back();
    }
}
