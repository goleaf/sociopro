<?php

namespace Tests\Feature;

use App\Enums\PaytmTransactionStatus;
use App\Models\Payment_gateway;
use App\Models\PaymentHistoryEntry;
use App\Models\Sponsor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentCastAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_gateway_casts_flags_and_key_payloads_without_serializing_secrets(): void
    {
        $gateway = Payment_gateway::create([
            'identifier' => 'custom_gateway',
            'currency' => 'USD',
            'title' => 'Custom Gateway',
            'description' => 'Custom gateway',
        ]);
        $gateway->forceFill([
            'keys' => [
                'public_key' => 'pk_test_public',
                'secret_key' => 'sk_test_secret',
            ],
            'model_name' => 'CustomGateway',
            'test_mode' => '0',
            'status' => '1',
            'is_addon' => '1',
        ])->save();

        $gateway->refresh();

        $this->assertSame([
            'public_key' => 'pk_test_public',
            'secret_key' => 'sk_test_secret',
        ], $gateway->keys);
        $this->assertSame($gateway->keys, $gateway->decodedKeys());
        $this->assertTrue($gateway->status);
        $this->assertFalse($gateway->test_mode);
        $this->assertTrue($gateway->is_addon);
        $this->assertArrayNotHasKey('keys', $gateway->toArray());
    }

    public function test_payment_history_casts_identifiers_money_and_transaction_payloads(): void
    {
        $entry = new PaymentHistoryEntry;
        $entry->forceFill([
            'item_type' => 'badge',
            'item_id' => '42',
            'user_id' => '7',
            'amount' => '19.5',
            'currency' => 'USD',
            'identifier' => 'paytm',
            'transaction_keys' => [
                'gateway_reference' => 'TXN-123',
                'token' => 'provider-token',
            ],
        ])->save();

        $entry->refresh();

        $this->assertSame(42, $entry->item_id);
        $this->assertSame(7, $entry->user_id);
        $this->assertSame('19.50', $entry->amount);
        $this->assertSame([
            'gateway_reference' => 'TXN-123',
            'token' => 'provider-token',
        ], $entry->transaction_keys);
        $this->assertArrayNotHasKey('transaction_keys', $entry->toArray());

        $entry->status = PaytmTransactionStatus::Successful->value;

        $this->assertSame(PaytmTransactionStatus::Successful, $entry->status);
    }

    public function test_sponsor_casts_payment_amount_status_and_schedule_dates(): void
    {
        $sponsor = new Sponsor;
        $sponsor->forceFill([
            'user_id' => '9',
            'name' => 'Summer Campaign',
            'paid_amount' => '49.9',
            'status' => '1',
            'start_date' => '2026-07-01 09:00:00',
            'end_date' => '2026-07-31 17:30:00',
        ])->save();

        $sponsor->refresh();

        $this->assertSame(9, $sponsor->user_id);
        $this->assertSame('49.90', $sponsor->paid_amount);
        $this->assertSame(1, $sponsor->status);
        $this->assertSame('2026-07-01 09:00:00', $sponsor->start_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-31 17:30:00', $sponsor->end_date->format('Y-m-d H:i:s'));
    }
}
