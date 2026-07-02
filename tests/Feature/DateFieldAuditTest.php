<?php

namespace Tests\Feature;

use Tests\TestCase;

class DateFieldAuditTest extends TestCase
{
    public function test_project_documents_date_field_schema_and_timezone_findings(): void
    {
        $path = base_path('docs/date-field-audit.md');

        $this->assertFileExists($path);

        $contents = file_get_contents($path) ?: '';

        foreach ([
            'created_at',
            'updated_at',
            'deleted_at',
            'published_at',
            'verified_at',
            'paid_at',
            'expires_at',
            'personal_access_tokens.expires_at',
            'Asia/Dhaka',
            'legacy string or epoch timestamps',
        ] as $requiredText) {
            $this->assertStringContainsString($requiredText, $contents);
        }
    }
}
