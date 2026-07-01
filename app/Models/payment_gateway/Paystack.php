<?php

namespace App\Models\payment_gateway;

use App\Models\Payment_gateway;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Throwable;

class Paystack extends Model
{
    use HasFactory;

    public function payment_status($identifier = '', array $transaction_keys = []): bool
    {
        $reference = trim((string) ($transaction_keys['reference'] ?? ''));

        if ($reference === '') {
            return false;
        }

        $paymentGateway = Payment_gateway::where('identifier', $identifier)->first();

        if (! $paymentGateway) {
            return false;
        }

        $keys = json_decode($paymentGateway->keys, true) ?: [];
        $secretKey = $this->secretKey($paymentGateway, $keys);

        if (! $secretKey) {
            return false;
        }

        try {
            $response = Http::acceptJson()
                ->withToken($secretKey)
                ->timeout(10)
                ->get('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
        } catch (Throwable) {
            return false;
        }

        return $response->json('status') === true
            && $response->json('data.status') === 'success';
    }

    private function secretKey(Payment_gateway $paymentGateway, array $keys): ?string
    {
        if ((int) $paymentGateway->test_mode === 1) {
            return $keys['secret_test_key'] ?? null;
        }

        return $keys['secret_live_key'] ?? null;
    }
}
