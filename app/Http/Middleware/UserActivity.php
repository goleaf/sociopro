<?php

namespace App\Http\Middleware;

use Cache;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class UserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $user->lastActive = Carbon::now();
        $user->save();

        if (Auth::check()) {
            $expiresat = Carbon::now()->addMinutes(5);
            Cache::put('user-is-online-'.Auth::user()->id, true, $expiresat);
        }

        return $next($request);
    }
}
