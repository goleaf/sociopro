<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ApiHttpContractAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_validation_without_json_accept_header_returns_json_without_redirect(): void
    {
        $response = $this->withHeaders(['Accept' => 'text/html'])
            ->post(route('api.auth.signup'), []);

        $response
            ->assertOk()
            ->assertJsonStructure(['validationError']);

        $this->assertApiResponseContract($response);
    }

    public function test_protected_api_authentication_failure_without_json_accept_header_returns_legacy_json_without_redirect(): void
    {
        $response = $this->withHeaders(['Accept' => 'text/html'])
            ->get(route('api.marketplace.index'));

        $response
            ->assertOk()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access',
                'error' => [
                    'code' => 'AUTHENTICATION_ERROR',
                    'http_status' => 401,
                ],
            ]);

        $this->assertApiResponseContract($response);
    }

    public function test_laravel_validation_exceptions_on_api_routes_return_json_without_redirect(): void
    {
        $user = $this->activeUser();
        $token = $user->createToken('api-http-contract-test')->plainTextToken;

        $response = $this->withHeaders(['Accept' => 'text/html'])
            ->withToken($token)
            ->post(route('api.blogs.store'), []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'category']);

        $this->assertApiResponseContract($response);
    }

    public function test_api_http_contract_audit_documents_compatibility_and_deprecations(): void
    {
        $audit = file_get_contents(base_path('docs/api-http-contract-audit.md'));

        $this->assertIsString($audit);
        $this->assertStringContainsString('HTTP verbs', $audit);
        $this->assertStringContainsString('idempotency', $audit);
        $this->assertStringContainsString('JSON-only behavior', $audit);
        $this->assertStringContainsString('Validation responses', $audit);
        $this->assertStringContainsString('Deprecations', $audit);
    }

    private function assertApiResponseContract(TestResponse $response): void
    {
        $this->assertFalse($response->baseResponse->isRedirection());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('content-type'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertSame('0', $response->headers->get('Expires'));
        $this->assertStringContainsString('Accept', (string) $response->headers->get('Vary'));
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'user_role' => UserRole::General->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
        ]);
    }
}
