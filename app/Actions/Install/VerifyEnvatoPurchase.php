<?php

namespace App\Actions\Install;

use Illuminate\Support\Facades\Http;
use Throwable;

class VerifyEnvatoPurchase
{
    public function handle(string $purchaseCode): bool
    {
        $purchaseCode = trim($purchaseCode);
        $personalToken = config('services.envato.personal_token');

        if ($purchaseCode === '' || ! $personalToken) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $personalToken,
            ])
                ->timeout(5)
                ->get(
                    'https://api.envato.com/v1/market/private/user/verify-purchase:' . $purchaseCode . '.json',
                    ['code' => $purchaseCode]
                );
        } catch (Throwable) {
            return false;
        }

        return count($response->json('verify-purchase', [])) > 0;
    }
}
