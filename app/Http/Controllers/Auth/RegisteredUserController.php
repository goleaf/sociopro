<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Support\Validation\DateTimeRules;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Session;

class RegisteredUserController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (get_settings('public_signup') != 1) {
            Session::flash('error_message', get_phrase('Public signup not allowed'));

            return redirect()->route('login');
        }

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'timezone' => DateTimeRules::timezoneOrDefault($validated['timezone'] ?? null),
        ]);
        $user->forceFill([
            'user_role' => UserRole::General->value,
            'username' => rand(100000, 999999),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'password' => Hash::make($validated['password']),
            'status' => UserAccountStatus::Active->value,
            'lastActive' => Carbon::now(),
            'created_at' => time(),
        ])->save();

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
