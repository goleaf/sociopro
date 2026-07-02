<?php

namespace App\Http\Middleware;

use App\Enums\PaymentGatewayIdentifier;
use App\Models\PaymentGateway;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyPaymentWebhook
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        if ($provider !== PaymentGatewayIdentifier::Paystack->value) {
            return $this->reject($request, $provider, 'unsupported_provider', Response::HTTP_NOT_FOUND);
        }

        $routeIdentifier = $request->route('identifier');
        $identifier = is_scalar($routeIdentifier) ? (string) $routeIdentifier : '';

        if ($identifier !== $provider) {
            return $this->reject($request, $provider, 'provider_mismatch', Response::HTTP_NOT_FOUND);
        }

        $gateway = PaymentGateway::query()
            ->select(['id', 'identifier', 'keys', 'test_mode'])
            ->forIdentifier($provider)
            ->first();

        if (! $gateway instanceof PaymentGateway) {
            return $this->reject($request, $provider, 'gateway_not_found', Response::HTTP_NOT_FOUND);
        }

        $secret = $this->paystackSecret($gateway);

        if ($secret === null) {
            return $this->reject($request, $provider, 'missing_secret', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $config = $this->webhookConfig($provider);
        $signature = $this->signature($request, $config);

        if (! $this->validPaystackSignature($request, $secret, $signature)) {
            return $this->reject($request, $provider, 'invalid_signature', Response::HTTP_UNAUTHORIZED);
        }

        $timestampResponse = $this->validateTimestamp($request, $provider, $config);
        if ($timestampResponse instanceof Response) {
            return $timestampResponse;
        }

        return $this->handleReplay($request, $next, $provider, $signature, $config);
    }

    private function paystackSecret(PaymentGateway $gateway): ?string
    {
        $keys = $gateway->decodedKeys();
        $key = $gateway->isInTestMode()
            ? ($keys['secret_test_key'] ?? null)
            : ($keys['secret_live_key'] ?? null);

        if (! is_string($key) || trim($key) === '') {
            return null;
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function signature(Request $request, array $config): string
    {
        $header = $this->stringConfig($config, 'signature_header', 'X-Paystack-Signature');

        return strtolower(trim((string) $request->headers->get($header, '')));
    }

    private function validPaystackSignature(Request $request, string $secret, string $signature): bool
    {
        if ($signature === '') {
            return false;
        }

        $expectedSignature = hash_hmac('sha512', $request->getContent(), $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function validateTimestamp(Request $request, string $provider, array $config): ?Response
    {
        $header = $this->stringConfig($config, 'timestamp_header', 'X-Sociopro-Timestamp');
        $timestamp = trim((string) $request->headers->get($header, ''));
        $requiresTimestamp = (bool) ($config['require_timestamp'] ?? false);

        if ($timestamp === '') {
            return $requiresTimestamp
                ? $this->reject($request, $provider, 'missing_timestamp', Response::HTTP_BAD_REQUEST)
                : null;
        }

        $timestampValue = filter_var($timestamp, FILTER_VALIDATE_INT);

        if (! is_int($timestampValue)) {
            return $this->reject($request, $provider, 'invalid_timestamp', Response::HTTP_BAD_REQUEST);
        }

        $tolerance = max(1, (int) ($config['timestamp_tolerance_seconds'] ?? 300));

        if (abs(now()->timestamp - $timestampValue) > $tolerance) {
            return $this->reject($request, $provider, 'stale_timestamp', Response::HTTP_BAD_REQUEST);
        }

        return null;
    }

    /**
     * @param  Closure(Request): Response  $next
     * @param  array<string, mixed>  $config
     */
    private function handleReplay(Request $request, Closure $next, string $provider, string $signature, array $config): Response
    {
        $cacheKey = $this->replayCacheKey($request, $provider, $signature, $config);
        $ttl = max(60, (int) ($config['replay_ttl_seconds'] ?? 86400));

        if (! Cache::add($cacheKey, true, $ttl)) {
            Log::info('payment_webhook_duplicate', $this->logContext($request, $provider, 'duplicate'));

            return response('Duplicate webhook accepted.', Response::HTTP_OK);
        }

        try {
            $response = $next($request);
        } catch (Throwable $throwable) {
            Cache::forget($cacheKey);

            throw $throwable;
        }

        if ($response->isServerError()) {
            Cache::forget($cacheKey);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function replayCacheKey(Request $request, string $provider, string $signature, array $config): string
    {
        $header = $this->stringConfig($config, 'idempotency_header', 'X-Paystack-Event');
        $eventId = trim((string) $request->headers->get($header, ''));
        $material = $eventId !== ''
            ? 'event:'.$eventId
            : 'payload:'.hash('sha256', $request->getContent());

        return 'payment-webhook:'.$provider.':'.hash('sha256', $material.':'.$signature);
    }

    private function reject(Request $request, string $provider, string $reason, int $status): Response
    {
        Log::warning('payment_webhook_rejected', $this->logContext($request, $provider, $reason));

        return response('Webhook rejected.', $status);
    }

    /**
     * @return array<string, string>
     */
    private function logContext(Request $request, string $provider, string $reason): array
    {
        return [
            'provider' => $provider,
            'reason' => $reason,
            'route' => $request->route()?->getName() ?: '',
            'client_ip' => $request->ip() ?: '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function webhookConfig(string $provider): array
    {
        $config = config('security.webhooks.'.$provider, []);

        return is_array($config) ? $config : [];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function stringConfig(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? $default;

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
