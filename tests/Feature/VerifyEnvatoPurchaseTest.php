<?php

namespace Tests\Feature;

use App\Actions\Install\VerifyEnvatoPurchase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerifyEnvatoPurchaseTest extends TestCase
{
    public function test_it_does_not_call_envato_without_a_token(): void
    {
        config(['services.envato.personal_token' => null]);

        Http::fake();

        $this->assertFalse($this->makeAction()->handle('purchase-code'));

        Http::assertNothingSent();
    }

    public function test_it_verifies_purchase_with_envato_api(): void
    {
        config(['services.envato.personal_token' => 'envato-token']);

        Http::fake([
            'api.envato.com/*' => Http::response([
                'verify-purchase' => [
                    'item_id' => 123,
                ],
            ]),
        ]);

        $this->assertTrue($this->makeAction()->handle('purchase-code'));

        Http::assertSent(function (Request $request) {
            return $request->method() === 'GET'
                && $request->hasHeader('Authorization', 'Bearer envato-token')
                && $request->hasHeader('Accept', 'application/json')
                && str_contains($request->url(), 'verify-purchase:purchase-code.json')
                && str_contains($request->url(), 'code=purchase-code');
        });
    }

    public function test_it_rejects_empty_envato_responses(): void
    {
        config(['services.envato.personal_token' => 'envato-token']);

        Http::fake([
            'api.envato.com/*' => Http::response([
                'verify-purchase' => [],
            ]),
        ]);

        $this->assertFalse($this->makeAction()->handle('purchase-code'));
    }

    public function test_it_returns_false_when_envato_request_fails(): void
    {
        config(['services.envato.personal_token' => 'envato-token']);

        Http::fake(function () {
            throw new ConnectionException('Connection failed.');
        });

        $this->assertFalse($this->makeAction()->handle('purchase-code'));
    }

    private function makeAction(): VerifyEnvatoPurchase
    {
        return new VerifyEnvatoPurchase();
    }
}
