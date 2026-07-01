<?php

namespace App\Http\Middleware;

use Cache;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserActivity
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
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
