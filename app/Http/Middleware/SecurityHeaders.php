<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('security_headers.enabled', true)) {
            return $response;
        }

        foreach ($this->headersForRequest($request) as $header => $value) {
            if (is_string($value) && $value !== '') {
                $response->headers->set($header, $value);
            }
        }

        $contentSecurityPolicy = $this->contentSecurityPolicyForRequest($request);
        if ($contentSecurityPolicy !== null) {
            $header = config('security_headers.csp.report_only', false)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $response->headers->set($header, $contentSecurityPolicy);
        }

        $strictTransportSecurity = $this->strictTransportSecurity($request);
        if ($strictTransportSecurity !== null) {
            $response->headers->set('Strict-Transport-Security', $strictTransportSecurity);
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function headersForRequest(Request $request): array
    {
        $headers = config('security_headers.headers', []);
        $headers = is_array($headers) ? $headers : [];

        foreach ($this->matchingOverrides($request) as $override) {
            $overrideHeaders = $override['headers'] ?? [];
            if (is_array($overrideHeaders)) {
                $headers = array_replace($headers, $overrideHeaders);
            }
        }

        return $headers;
    }

    private function contentSecurityPolicyForRequest(Request $request): ?string
    {
        if (! config('security_headers.csp.enabled', true)) {
            return null;
        }

        $directives = config('security_headers.csp.directives', []);
        if (! is_array($directives)) {
            return null;
        }

        foreach ($this->matchingOverrides($request) as $override) {
            $overrideDirectives = $override['csp']['directives'] ?? [];
            if (is_array($overrideDirectives)) {
                $directives = array_replace($directives, $overrideDirectives);
            }
        }

        return $this->serializeContentSecurityPolicy($directives);
    }

    private function strictTransportSecurity(Request $request): ?string
    {
        if (! $request->isSecure() || ! config('security_headers.hsts.enabled', true)) {
            return null;
        }

        $parts = ['max-age='.(int) config('security_headers.hsts.max_age', 31536000)];

        if (config('security_headers.hsts.include_subdomains', true)) {
            $parts[] = 'includeSubDomains';
        }

        if (config('security_headers.hsts.preload', false)) {
            $parts[] = 'preload';
        }

        return implode('; ', $parts);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function matchingOverrides(Request $request): array
    {
        $overrides = config('security_headers.route_overrides', []);
        if (! is_array($overrides)) {
            return [];
        }

        $matched = [];
        foreach ($overrides as $pattern => $override) {
            if (is_string($pattern) && is_array($override) && $request->is($pattern)) {
                $matched[] = $override;
            }
        }

        return $matched;
    }

    /**
     * @param  array<string, mixed>  $directives
     */
    private function serializeContentSecurityPolicy(array $directives): string
    {
        $segments = [];

        foreach ($directives as $directive => $values) {
            if (! is_string($directive) || $directive === '' || $values === false || $values === null) {
                continue;
            }

            if ($values === true || $values === []) {
                $segments[] = $directive;

                continue;
            }

            $values = array_filter((array) $values, fn ($value): bool => is_string($value) && $value !== '');
            if ($values === []) {
                continue;
            }

            $segments[] = $directive.' '.implode(' ', $values);
        }

        return implode('; ', $segments);
    }
}
