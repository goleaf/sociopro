<?php

namespace App\Services\Zoom;

use App\Models\Setting;
use DateTime;
use DateTimeZone;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class ZoomMeetingClient
{
    private const API_BASE_URL = 'https://api.zoom.us/v2/';

    public function __construct(private readonly Factory $http) {}

    public function get(string $path, array $query = []): Response
    {
        return $this->request()->get($this->url($path), $query);
    }

    public function post(string $path, array $body = []): Response
    {
        return $this->request()->post($this->url($path), $body);
    }

    public function patch(string $path, array $body = []): Response
    {
        return $this->request()->patch($this->url($path), $body);
    }

    public function delete(string $path, array $body = []): Response
    {
        return $this->request()->delete($this->url($path), $body);
    }

    public function toZoomTimeFormat(int|string $dateTime): string
    {
        $date = date('d-m-Y H:i:s', (int) $dateTime);

        try {
            $date = new DateTime($date);

            return $date->format('Y-m-d\TH:i:s');
        } catch (Exception $exception) {
            Log::warning('zoom_time_conversion_failed', [
                'operation' => 'to_zoom_time_format',
                'exception' => $exception::class,
            ]);

            return '';
        }
    }

    public function toUnixTimeStamp(string $dateTime, string $timezone): int|string
    {
        try {
            $date = new DateTime($dateTime, new DateTimeZone($timezone));

            return $date->getTimestamp();
        } catch (Exception $exception) {
            Log::warning('zoom_time_conversion_failed', [
                'operation' => 'to_unix_timestamp',
                'exception' => $exception::class,
            ]);

            return '';
        }
    }

    private function request(): PendingRequest
    {
        return $this->http->withHeaders([
            'authorization' => 'Bearer '.$this->generateToken(),
            'content-type' => 'application/json',
        ]);
    }

    private function generateToken(): string
    {
        $credentials = $this->credentials();
        $payload = [
            'iss' => (string) ($credentials['api_key'] ?? ''),
            'exp' => strtotime('+1 minute'),
        ];

        return JWT::encode($payload, (string) ($credentials['api_secret'] ?? ''), 'HS256');
    }

    /**
     * @return array<string, mixed>
     */
    private function credentials(): array
    {
        $configuration = Setting::query()
            ->where('type', 'zoom_configuration')
            ->value('description');

        return json_decode(is_string($configuration) ? $configuration : '', true) ?: [];
    }

    private function url(string $path): string
    {
        return self::API_BASE_URL.ltrim($path, '/');
    }
}
