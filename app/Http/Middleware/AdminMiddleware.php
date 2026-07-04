<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        $isActiveAdmin = $user->isAdmin() && $user->hasActiveAccount();

        if ($isActiveAdmin) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403);
        }

        if ($user->isAdmin()) {
            return redirect()->route('frontend.disable_view');
        }

        return redirect()->route('timeline');
    }
}
