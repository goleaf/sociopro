<?php

namespace App\Http\Middleware;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            auth()->user()->user_role == UserRole::General->value
            && (int) auth()->user()->status === UserAccountStatus::Active->value
            || auth()->user()->user_role == UserRole::Admin->value
        ) {
            return $next($request);
        } else {
            return redirect()->route('frontend.disable_view');
        }
    }
}
