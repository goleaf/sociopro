<?php

namespace App\Http\Middleware;

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
        if (auth()->user()->user_role == 'general' && auth()->user()->status == '1' || auth()->user()->user_role == 'admin') {
            return $next($request);
        } else {
            return redirect()->route('frontend.disable_view');
        }
    }
}
