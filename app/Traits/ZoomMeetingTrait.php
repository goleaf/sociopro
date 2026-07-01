<?php

namespace App\Traits;

use DateTime;
use DateTimeZone;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * trait ZoomMeetingTrait
 */
trait ZoomMeetingTrait
{
    public $zoom_api_key;

    public $zoom_secret_key;

    private $zoom_api_url;

    private function get_zoom_keys(): void {}

    private function generateZoomToken(): string
    {
        $zoom_configuration = get_settings('zoom_configuration', 'decoded');

        $zoom_api_key = $zoom_configuration['api_key'];
        $zoom_secret_key = $zoom_configuration['api_secret'];
        $zoom_api_url = 'https://api.zoom.us/v2/';

        $key = $zoom_api_key;
        $secret = $zoom_secret_key;
        $payload = [
            'iss' => $key,
            'exp' => strtotime('+1 minute'),
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    private function retrieveZoomUrl(): string
    {
        return 'https://api.zoom.us/v2/';
    }

    private function zoomRequest(): PendingRequest
    {
        $jwt = $this->generateZoomToken();

        return Http::withHeaders([
            'authorization' => 'Bearer '.$jwt,
            'content-type' => 'application/json',
        ]);
    }

    public function zoomGet(string $path, array $query = []): Response
    {
        $url = $this->retrieveZoomUrl();
        $request = $this->zoomRequest();

        return $request->get($url.$path, $query);
    }

    public function zoomPost(string $path, array $body = []): Response
    {
        $url = $this->retrieveZoomUrl();
        $request = $this->zoomRequest();

        return $request->post($url.$path, $body);
    }

    public function zoomPatch(string $path, array $body = []): Response
    {
        $url = $this->retrieveZoomUrl();
        $request = $this->zoomRequest();

        return $request->patch($url.$path, $body);
    }

    public function zoomDelete(string $path, array $body = []): Response
    {
        $url = $this->retrieveZoomUrl();
        $request = $this->zoomRequest();

        return $request->delete($url.$path, $body);
    }

    public function toZoomTimeFormat(string $dateTime): string
    {
        $date = date('d-m-Y H:i:s', (int) $dateTime);

        try {
            $date = new DateTime($date);

            return $date->format('Y-m-d\TH:i:s');
        } catch (Exception $e) {
            Log::error('ZoomJWT->toZoomTimeFormat : '.$e->getMessage());

            return '';
        }
    }

    public function toUnixTimeStamp(string $dateTime, string $timezone): int|string
    {
        try {
            $date = new DateTime($dateTime, new DateTimeZone($timezone));

            return $date->getTimestamp();
        } catch (Exception $e) {
            Log::error('ZoomJWT->toUnixTimeStamp : '.$e->getMessage());

            return '';
        }
    }
}
