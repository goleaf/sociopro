<?php

namespace App\Http\Middleware;

use App\Models\User;
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
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        $isActiveGeneralUser = $user->isGeneralUser() && $user->hasActiveAccount();
        $isAdmin = $user->isAdmin();

        if ($isActiveGeneralUser || $isAdmin) {
            return $next($request);
        }

        return redirect()->route('frontend.disable_view');
    }
}
