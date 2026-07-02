<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiResponseContract
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Vary', $this->varyHeader($response->headers->get('Vary')));

        return $response;
    }

    private function varyHeader(?string $existing): string
    {
        $values = array_values(array_filter(array_map('trim', explode(',', (string) $existing))));

        foreach ($values as $value) {
            if (strcasecmp($value, 'Accept') === 0) {
                return implode(', ', $values);
            }
        }

        $values[] = 'Accept';

        return implode(', ', $values);
    }
}
