<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UserActivity
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $user->lastActive = Carbon::now();
        $user->save();

        $expiresat = Carbon::now()->addMinutes(5);
        Cache::put('user-is-online-'.$user->id, true, $expiresat);

        return $next($request);
    }
}
