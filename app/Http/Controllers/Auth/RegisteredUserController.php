<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
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
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'user_role' => UserRole::General->value,
            'username' => rand(100000, 999999),
            'name' => $request->name,
            'email' => $request->email,
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'timezone' => $request->timezone,
            'password' => Hash::make($request->password),
            'status' => UserAccountStatus::Disabled->value,
            'lastActive' => Carbon::now(),
            'created_at' => time(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
