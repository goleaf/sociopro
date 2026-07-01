<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->user_role == 'general' && auth()->user()->status == '1' || auth()->user()->user_role == 'admin') {
            return $next($request);
        } else {
            return redirect()->route('frontend.disable_view');
        }
    }
}
