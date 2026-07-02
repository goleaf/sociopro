<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiErrorFormatDocumentationTest extends TestCase
{
    public function test_api_error_format_documentation_covers_required_categories(): void
    {
        $path = base_path('docs/api-error-format.md');

        $this->assertFileExists($path);

        $contents = file_get_contents($path) ?: '';

        foreach ([
            'VALIDATION_ERROR',
            'AUTHENTICATION_ERROR',
            'AUTHORIZATION_ERROR',
            'NOT_FOUND',
            'CONFLICT',
            'RATE_LIMITED',
            'DOMAIN_ERROR',
            'SERVER_ERROR',
            'legacy unversioned API',
            'new_notifications',
            'older_notifications',
        ] as $expectedText) {
            $this->assertStringContainsString($expectedText, $contents);
        }
    }
}
